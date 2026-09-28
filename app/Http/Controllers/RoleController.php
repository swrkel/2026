<?php

namespace App\Http\Controllers;

use App\Business;
use App\SellingPriceGroup;
use App\User;
use App\Utils\ModuleUtil;
use App\Utils\SidebarPermissionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    protected ModuleUtil $moduleUtil;

    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Read permissions from both the normal form fields and the defensive JSON
     * payload used by the large Role editor.
     *
     * @return array<int, string>
     */
    private function extractPermissions(Request $request): array
    {
        $permissions = (array) $request->input('permissions', []);
        $payload = $request->input('permissions_payload');

        if (is_string($payload) && $payload !== '') {
            $decoded = json_decode($payload, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $permissions = array_merge($permissions, $decoded);
            }
        }

        return array_values(array_unique(array_filter(array_map(
            static fn ($permission): string => trim((string) $permission),
            $permissions
        ), static function (string $permission): bool {
            // `superadmin` is a central-system identity ability, never a
            // business-role permission. Reject a forged form value even if an
            // old database already contains a Permission row with this name.
            return $permission !== '' && strcasecmp($permission, 'superadmin') !== 0;
        })));
    }

    public function index()
    {
        if (!auth()->user()->can('roles.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');

        if (request()->ajax()) {
            $roleTable = (new Role())->getTable();
            $userTable = (new User())->getTable();
            $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');

            $roles = Role::where($roleTable . '.business_id', $businessId)
                ->select([
                    $roleTable . '.name',
                    $roleTable . '.id',
                    $roleTable . '.is_default',
                    $roleTable . '.business_id',
                ])
                ->selectSub(function ($query) use ($modelHasRolesTable, $userTable, $roleTable, $businessId) {
                    $query->from($modelHasRolesTable)
                        ->join($userTable, $userTable . '.id', '=', $modelHasRolesTable . '.model_id')
                        ->selectRaw('COUNT(*)')
                        ->whereColumn($modelHasRolesTable . '.role_id', $roleTable . '.id')
                        ->where($modelHasRolesTable . '.model_type', User::class)
                        ->where($userTable . '.business_id', $businessId);
                }, 'assigned_users_count')
                ->orderByDesc($roleTable . '.id');

            if (!$this->moduleUtil->hasThePermissionInSubscription($businessId, 'enable_petro_module')) {
                $roles->where($roleTable . '.name', '!=', 'Pump Operator#' . $businessId);
            }

            $highlightRoleId = (int) request()->input('highlight_role_id', 0);
            $editRoleEnabled = $this->moduleUtil->hasThePermissionInSubscription($businessId, 'um_edit_role');
            $deleteRoleEnabled = $this->moduleUtil->hasThePermissionInSubscription($businessId, 'um_delete_role');

            return DataTables::of($roles)
                ->addColumn('action', function ($row) use ($businessId, $editRoleEnabled, $deleteRoleEnabled) {
                    $canModifyBuiltInRole = $this->canModifyRole($row, $businessId);
                    $assignedUsersCount = (int) ($row->assigned_users_count ?? 0);
                    $deleteBlocked = !$canModifyBuiltInRole || $assignedUsersCount > 0;
                    $deleteBlockedReason = '';

                    if (!$canModifyBuiltInRole) {
                        $deleteBlockedReason = 'This is a protected default role and cannot be deleted.';
                    } elseif ($assignedUsersCount > 0) {
                        $deleteBlockedReason = 'Delete is disabled because this role is assigned to one or more users.';
                    }

                    return view('role.partials.action', [
                        'roleId' => (int) $row->id,
                        'canModifyRole' => $canModifyBuiltInRole,
                        'deleteBlocked' => $deleteBlocked,
                        'deleteBlockedReason' => $deleteBlockedReason,
                        'assignedUsersCount' => $assignedUsersCount,
                        'editRoleEnabled' => $editRoleEnabled,
                        'deleteRoleEnabled' => $deleteRoleEnabled,
                    ])->render();
                })
                ->editColumn('name', fn ($row): string => $this->displayRoleName($row->name, $businessId))
                ->setRowClass(function ($row) use ($highlightRoleId): string {
                    return $highlightRoleId > 0 && (int) $row->id === $highlightRoleId
                        ? 'role-row-new'
                        : '';
                })
                ->removeColumn('id')
                ->removeColumn('is_default')
                ->removeColumn('business_id')
                ->removeColumn('assigned_users_count')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('role.index');
    }

    public function create()
    {
        if (!auth()->user()->can('roles.create')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');
        $sellingPriceGroups = SellingPriceGroup::where('business_id', $businessId)->get();
        $modulePermissions = $this->moduleUtil->getModuleData('user_permissions');
        $rolePermissions = [];
        $getPermissions = $this->getSubscriptionPermissions($businessId);
        $rolePermissionDetails = collect();

        return view('role.create')->with([
            'selling_price_groups' => $sellingPriceGroups,
            'module_permissions' => $modulePermissions,
            'role_permissions' => $rolePermissions,
            'get_permissions' => $getPermissions,
            'role_permission_details' => $rolePermissionDetails,
        ]);
    }

    public function store(Request $request)
    {
        if (!auth()->user()->can('roles.create')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        $businessId = (int) $request->session()->get('user.business_id');
        $roleName = trim((string) $request->input('name'));
        $this->assertRoleNameMayBeUsed($roleName, $businessId);
        $fullRoleName = $roleName . '#' . $businessId;

        if (Role::where('name', $fullRoleName)->where('business_id', $businessId)->exists()) {
            return redirect('roles')->with('status', [
                'success' => 0,
                'msg' => __('user.role_already_exists'),
            ]);
        }

        $permissions = $this->permissionsWithSellingPriceGroups($request);
        $newRoleId = null;

        try {
            DB::transaction(function () use ($request, $businessId, $fullRoleName, $permissions, &$newRoleId): void {
                $business = Business::findOrFail($businessId);
                $business->day_end_enable = $request->filled('day_end_enable')
                    ? $request->input('day_end_enable')
                    : 0;
                $business->day_end = $request->filled('day_end_disable')
                    ? $request->input('day_end_disable')
                    : 0;
                $business->save();

                $role = new Role();
                $role->name = $fullRoleName;
                $role->guard_name = 'web';
                $role->business_id = $businessId;
                $role->is_service_staff = (int) $request->input('is_service_staff', 0);
                $role->save();

                $this->createPermissionsIfMissing($permissions);
                // Always sync, including an empty array, so the stored role exactly
                // matches what the user selected.
                $role->syncPermissions($permissions);
                $newRoleId = (int) $role->id;
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect('roles')
                ->with('status', [
                    'success' => 1,
                    'msg' => __('user.role_added'),
                ])
                ->with('new_role_id', $newRoleId);
        } catch (\Throwable $e) {
            Log::emergency('Role creation failed.', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'business_id' => $businessId,
            ]);

            return redirect('roles')->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function show($id)
    {
        if (!auth()->user()->can('roles.view')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');
        $role = Role::where('business_id', $businessId)
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($id);

        $assignedUsers = $this->assignedUsersForRole((int) $role->id, $businessId);
        $displayRoleName = $this->displayRoleName($role->name, $businessId);
        $canModifyRole = $this->canModifyRole($role, $businessId);
        $editRoleEnabled = $this->moduleUtil->hasThePermissionInSubscription($businessId, 'um_edit_role');

        return view('role.show', compact(
            'role',
            'assignedUsers',
            'displayRoleName',
            'canModifyRole',
            'editRoleEnabled'
        ));
    }

    public function edit($id)
    {
        if (!auth()->user()->can('roles.update')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');
        $role = Role::where('business_id', $businessId)
            ->with(['permissions' => fn ($query) => $query->orderBy('name')])
            ->findOrFail($id);

        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $rolePermissionDetails = $role->permissions->values();
        $sellingPriceGroups = SellingPriceGroup::where('business_id', $businessId)->get();
        $modulePermissions = $this->moduleUtil->getModuleData('user_permissions');
        $getPermissions = $this->getSubscriptionPermissions($businessId);

        $allSalesAgentPermissions = [
            'sales_agent.view',
            'sales_agent.add',
            'sales_agent.edit',
            'sales_agent.delete',
            'sales_agent.add_commission',
        ];
        if (count(array_intersect($rolePermissions, $allSalesAgentPermissions)) > 0) {
            $getPermissions['sales_agent_module'] = true;
        }

        // Always provide users assigned to the role. The old implementation only
        // loaded this list when Petro PD was enabled, which made role usage unclear.
        $users = $this->assignedUsersForRole((int) $role->id, $businessId);
        $displayRoleName = $this->displayRoleName($role->name, $businessId);

        return view('role.edit')->with([
            'role' => $role,
            'role_permissions' => $rolePermissions,
            'role_permission_details' => $rolePermissionDetails,
            'selling_price_groups' => $sellingPriceGroups,
            'module_permissions' => $modulePermissions,
            'get_permissions' => $getPermissions,
            'users' => $users,
            'display_role_name' => $displayRoleName,
        ]);
    }

    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('roles.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => ['required', 'string', 'max:191'],
        ]);

        $businessId = (int) $request->session()->get('user.business_id');
        $roleName = trim((string) $request->input('name'));
        $this->assertRoleNameMayBeUsed($roleName, $businessId);
        $fullRoleName = $roleName . '#' . $businessId;
        $permissions = $this->permissionsWithSellingPriceGroups($request);

        try {
            $role = Role::where('business_id', $businessId)->findOrFail($id);

            if (Role::where('name', $fullRoleName)
                ->where('business_id', $businessId)
                ->where('id', '!=', $role->id)
                ->exists()) {
                return redirect('roles')->with('status', [
                    'success' => 0,
                    'msg' => __('user.role_already_exists'),
                ]);
            }

            if (!$this->canModifyRole($role, $businessId)) {
                return redirect('roles')->with('status', [
                    'success' => 0,
                    'msg' => __('user.role_is_default'),
                ]);
            }

            DB::transaction(function () use ($request, $businessId, $role, $fullRoleName, $permissions): void {
                $business = Business::findOrFail($businessId);
                $business->day_end_enable = $request->filled('day_end_enable')
                    ? $request->input('day_end_enable')
                    : 0;
                $business->save();

                if ($role->name === 'Cashier#' . $businessId) {
                    $role->is_default = 0;
                }

                $role->is_service_staff = (int) $request->input('is_service_staff', 0);
                $role->name = $fullRoleName;
                $role->save();

                $this->createPermissionsIfMissing($permissions);
                // Important: sync an empty array too. This allows a user to remove
                // every permission and save the role correctly.
                $role->syncPermissions($permissions);
            });

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return redirect('roles')->with('status', [
                'success' => 1,
                'msg' => __('user.role_updated'),
            ]);
        } catch (\Throwable $e) {
            Log::emergency('Role update failed.', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'business_id' => $businessId,
                'role_id' => $id,
            ]);

            return redirect('roles')->with('status', [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    public function destroy($id)
    {
        if (!auth()->user()->can('roles.delete')) {
            abort(403, 'Unauthorized action.');
        }

        $businessId = (int) request()->session()->get('user.business_id');

        try {
            $role = Role::where('business_id', $businessId)->findOrFail($id);

            if (!$this->canModifyRole($role, $businessId)) {
                $output = [
                    'success' => false,
                    'msg' => 'This is a protected default role and cannot be deleted. You may View it instead.',
                ];
            } elseif ($this->roleHasAssignedUsers((int) $role->id, $businessId)) {
                $output = [
                    'success' => false,
                    'msg' => 'This role cannot be deleted because it is assigned to one or more user records. You may View or Edit it instead.',
                ];
            } else {
                DB::transaction(fn () => $role->delete());
                app(PermissionRegistrar::class)->forgetCachedPermissions();

                $output = [
                    'success' => true,
                    'msg' => __('user.role_deleted'),
                ];
            }
        } catch (\Throwable $e) {
            Log::emergency('Role deletion failed.', [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'message' => $e->getMessage(),
                'business_id' => $businessId,
                'role_id' => $id,
            ]);

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if (request()->ajax() || request()->expectsJson()) {
            return response()->json($output);
        }

        return redirect('roles')->with('status', $output);
    }

    /** @return array<string, mixed> */
    private function getSubscriptionPermissions(int $businessId): array
    {
        $subscription = \Modules\Superadmin\Entities\Subscription::active_subscription($businessId);
        $permissions = !empty($subscription) ? (array) $subscription->package_details : [];

        $permissions['sales_agent_module'] = $this->moduleUtil->isModuleEnabled('SalesAgent')
            || (!empty($permissions['sales_agent_module']) && $permissions['sales_agent_module']);
        $permissions['petro_pd_module'] = !empty($permissions['petro_pd_module']);

        return $permissions;
    }

    /** @return array<int, string> */
    private function permissionsWithSellingPriceGroups(Request $request): array
    {
        $permissions = array_merge(
            $this->extractPermissions($request),
            (array) $request->input('spg_permissions', [])
        );

        return array_values(array_unique(array_filter(array_map(
            static fn ($permission): string => trim((string) $permission),
            $permissions
        ), static function (string $permission): bool {
            // Apply the central-ability guard after every form source is merged.
            // This closes the alternative spg_permissions[] payload path too.
            return $permission !== '' && strcasecmp($permission, 'superadmin') !== 0;
        })));
    }

    /** @param array<int, string> $permissions */
    private function createPermissionsIfMissing(array $permissions): void
    {
        if (empty($permissions)) {
            return;
        }

        $existingPermissions = Permission::where('guard_name', 'web')
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();

        foreach (array_diff($permissions, $existingPermissions) as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }
    }

    private function assertRoleNameMayBeUsed(string $roleName, int $businessId): void
    {
        if (strcasecmp($roleName, 'Admin') !== 0) {
            return;
        }

        $adminRoleName = 'Admin#' . $businessId;
        $allowed = SidebarPermissionUtil::hasSuperAdminBypass()
            || auth()->user()->hasRole($adminRoleName);

        if (! $allowed) {
            abort(403, 'Only an administrator can create or rename the Administrator role.');
        }
    }

    private function displayRoleName(string $roleName, int $businessId): string
    {
        $displayName = str_replace('#' . $businessId, '', $roleName);
        if (in_array($displayName, ['Admin', 'Cashier'], true)) {
            return __('lang_v1.' . $displayName);
        }

        return $displayName;
    }

    private function canModifyRole(object $role, int $businessId): bool
    {
        return !$role->is_default || in_array($role->name, [
            'Cashier#' . $businessId,
            'Supervisor#' . $businessId,
        ], true);
    }

    private function roleHasAssignedUsers(int $roleId, int $businessId): bool
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $userTable = (new User())->getTable();

        return DB::table($modelHasRolesTable)
            ->join($userTable, $userTable . '.id', '=', $modelHasRolesTable . '.model_id')
            ->where($modelHasRolesTable . '.role_id', $roleId)
            ->where($modelHasRolesTable . '.model_type', User::class)
            ->where($userTable . '.business_id', $businessId)
            ->exists();
    }

    private function assignedUsersForRole(int $roleId, int $businessId)
    {
        $modelHasRolesTable = config('permission.table_names.model_has_roles', 'model_has_roles');
        $userIds = DB::table($modelHasRolesTable)
            ->where('role_id', $roleId)
            ->where('model_type', User::class)
            ->pluck('model_id');

        return User::withTrashed()
            ->where('business_id', $businessId)
            ->whereIn('id', $userIds)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }
}
