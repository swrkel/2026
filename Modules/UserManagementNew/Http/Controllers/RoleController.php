<?php

namespace Modules\UserManagementNew\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Modules\UserManagementNew\Services\BusinessPermissionBridge;
use Modules\UserManagementNew\Services\RolePermissionService;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(
        private BusinessPermissionBridge $bridge,
        private RolePermissionService $permissions
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility('roles.view');
        $businessId = $this->businessId($request);
        $roleTable = (new Role())->getTable();
        $pivot = config('permission.table_names.model_has_roles', 'model_has_roles');

        $roles = Role::query()
            ->where($roleTable . '.business_id', $businessId)
            ->select($roleTable . '.*')
            ->selectSub(function ($query) use ($pivot, $roleTable) {
                $query->from($pivot)
                    ->selectRaw('COUNT(*)')
                    ->whereColumn($pivot . '.role_id', $roleTable . '.id')
                    ->where($pivot . '.model_type', User::class);
            }, 'users_count')
            ->orderBy('name')
            ->get();

        return view('usermanagementnew::roles.index', compact('roles', 'businessId'));
    }

    public function create(Request $request)
    {
        $this->authorizeAbility('roles.create');

        return $this->formData($request, new Role([
            'guard_name' => 'web',
            'business_id' => $this->businessId($request),
        ]));
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('roles.create');
        $businessId = $this->businessId($request);
        $name = $this->validatedName($request, $businessId);

        return DB::transaction(function () use ($request, $businessId, $name) {
            $role = new Role();
            $role->name = $name . '#' . $businessId;
            $role->guard_name = 'web';
            $role->business_id = $businessId;
            $role->is_service_staff = (int) $request->boolean('is_service_staff');
            $role->save();
            $this->sync($request, $role, $businessId);

            return redirect()->route('user-management-new.roles.index')
                ->with('status', ['success' => 1, 'msg' => 'Role created successfully.']);
        });
    }

    public function show(Request $request, int $role)
    {
        $this->authorizeAbility('roles.view');
        $businessId = $this->businessId($request);
        $role = $this->role($role, $businessId);
        $selected = array_fill_keys($this->permissions->selected($role), true);
        $sections = $this->bridge->sections($businessId);
        $rights = $this->permissions->rights();

        return view('usermanagementnew::roles.show', compact(
            'role', 'selected', 'sections', 'rights', 'businessId'
        ));
    }

    public function edit(Request $request, int $role)
    {
        $this->authorizeAbility('roles.update');

        return $this->formData(
            $request,
            $this->role($role, $this->businessId($request))
        );
    }

    public function update(Request $request, int $role)
    {
        $this->authorizeAbility('roles.update');
        $businessId = $this->businessId($request);
        $role = $this->role($role, $businessId);
        $name = $this->validatedName($request, $businessId, (int) $role->id);

        if ($this->protected($role, $businessId)) {
            return back()->with('status', [
                'success' => 0,
                'msg' => 'The protected Administrator role cannot be changed.',
            ]);
        }

        DB::transaction(function () use ($request, $role, $businessId, $name): void {
            $role->name = $name . '#' . $businessId;
            $role->is_service_staff = (int) $request->boolean('is_service_staff');
            $role->save();
            $this->sync($request, $role, $businessId);
        });

        return redirect()->route('user-management-new.roles.index')
            ->with('status', ['success' => 1, 'msg' => 'Role updated successfully.']);
    }

    public function destroy(Request $request, int $role)
    {
        $this->authorizeAbility('roles.delete');
        $businessId = $this->businessId($request);
        $role = $this->role($role, $businessId);

        if ($this->protected($role, $businessId)
            || DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
                ->where('role_id', $role->id)
                ->exists()) {
            return back()->with('status', [
                'success' => 0,
                /*
                 * S651: say WHICH reason, and in the wording the ticket asked for.
                 * "protected or assigned" left the user guessing which applied.
                 */
                'msg' => $this->protected($role, $businessId)
                    ? 'This is a protected role and cannot be deleted.'
                    : 'This role cannot be deleted because users have been assigned to it.',
            ]);
        }

        $role->delete();

        return redirect()->route('user-management-new.roles.index')
            ->with('status', ['success' => 1, 'msg' => 'Role deleted successfully.']);
    }

    private function formData(Request $request, Role $role)
    {
        $businessId = $this->businessId($request);
        $sections = $this->bridge->sections($businessId);
        $rights = $this->permissions->rights();
        $selected = $role->exists
            ? array_fill_keys($this->permissions->selected($role), true)
            : [];
        $displayName = $role->exists
            ? preg_replace('/#' . preg_quote((string) $businessId, '/') . '$/', '', $role->name)
            : '';

        /*
         | S640/S642: data the shared application-permission sections need.
         |
         | These are the same partials the legacy Role screen renders
         | (resources/views/role/sections/, driven by _sections.php), so both
         | screens offer one list maintained in one place.
         */
        $role_permissions = $role->exists
            ? $role->permissions()->pluck('name')->all()
            : [];

        $get_permissions = $this->subscriptionPackageDetails($businessId);
        $enabled_modules = (array) (session('business.enabled_modules') ?? []);
        $mode = $role->exists ? 'edit' : 'create';
        $users = [];

        /*
         * sections/70_access_selling_price.blade.php iterates this. The legacy
         * Role screen builds it the same way; without it that one section would
         * throw "Undefined variable $selling_price_groups" and take the whole
         * page down.
         */
        $selling_price_groups = collect();

        try {
            if (class_exists(\App\SellingPriceGroup::class)) {
                $selling_price_groups = \App\SellingPriceGroup::where('business_id', $businessId)->get();
            }
        } catch (\Throwable $e) {
            $selling_price_groups = collect();
        }

        return view('usermanagementnew::roles.form', compact(
            'role', 'sections', 'rights', 'selected', 'displayName', 'businessId',
            'role_permissions', 'get_permissions', 'enabled_modules', 'mode', 'users',
            'selling_price_groups'
        ));
    }

    private function sync(Request $request, Role $role, int $businessId): void
    {
        $sections = $this->bridge->sections($businessId);
        $selected = $this->permissions->sanitize(
            (array) $request->input('permissions', []),
            $sections
        );

        /*
         | IS2347: a child page is a real sidebar permission, therefore selecting
         | even one page must make its parent module visible. Previously the
         | middleware required umn.module.<module>.view as a separate prerequisite;
         | a role with one selected page could therefore end up with no visible
         | parent (or a 403). Derive only the parent View right - no other module
         | rights are granted.
         */
        $selected = $this->permissions->ensureParentModuleViewForSelectedPages(
            $selected,
            $sections
        );

        /*
         | FINAL HIERARCHY / DATA-PRESERVATION RULE:
         |
         | Level 1 (Manage Side Bar) or Level 2 (Manage Page New) can temporarily
         | remove a module/page from this Role form. Those hidden umn.* choices
         | must NOT be deleted simply because the browser could not submit them.
         | Preserve only permissions outside the currently visible catalogue;
         | visible unchecked permissions remain removable as normal.
         */
        if ($role->exists) {
            $visibleManaged = array_fill_keys($this->permissions->catalogue($sections), true);
            foreach ($this->permissions->selected($role) as $existingManaged) {
                if ($existingManaged === \Modules\UserManagementNew\Services\RolePermissionService::MANAGED) {
                    continue;
                }
                if (! isset($visibleManaged[$existingManaged])) {
                    $selected[] = $existingManaged;
                }
            }
            $selected = array_values(array_unique($selected));
        }

        /*
         | S640/S642: also persist the APPLICATION permissions.
         |
         | sanitize() keeps only umn.* keys, which are restrictive - they hide and
         | deny, they never grant. The form now renders the real permission
         | sections as well, and those arrive in the same permissions[] array.
         |
         | The marker field says whether the form actually rendered them. Present
         | means the submitted set is authoritative, so unticking a box removes
         | the permission. Absent means an older form, and the role keeps what it
         | has rather than being silently stripped.
         */
        $applicationPermissions = $request->has('application_permissions_rendered')
            ? $this->permissions->sanitizeApplicationPermissions(
                (array) $request->input('permissions', [])
            )
            : null;

        $this->permissions->sync($role, $selected, $applicationPermissions);
    }

    /**
     * S640/S642: package_details for the active subscription, which is what the
     * shared permission sections gate on. Resolved through the same Superadmin
     * entity the legacy Role screen uses, and failing open to an empty array so a
     * lookup problem hides sections rather than breaking the page.
     */
    private function subscriptionPackageDetails(int $businessId): array
    {
        try {
            $class = \Modules\Superadmin\Entities\Subscription::class;

            if (! class_exists($class)) {
                return [];
            }

            $subscription = $class::active_subscription($businessId);

            return empty($subscription) ? [] : (array) $subscription->package_details;
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function validatedName(Request $request, int $businessId, ?int $ignoreId = null): string
    {
        $request->merge(['full_role_name' => trim((string) $request->input('name')) . '#' . $businessId]);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191', 'not_regex:/#/'],
            'full_role_name' => [
                Rule::unique(config('permission.table_names.roles', 'roles'), 'name')
                    ->ignore($ignoreId),
            ],
        ]);

        return trim((string) $validated['name']);
    }

    private function role(int $id, int $businessId): Role
    {
        return Role::query()
            ->where('business_id', $businessId)
            ->with('permissions')
            ->findOrFail($id);
    }

    private function businessId(Request $request): int
    {
        $businessId = (int) ($request->session()->get('business.id')
            ?: $request->session()->get('user.business_id'));
        abort_if($businessId <= 0, 403, 'A business context is required.');

        return $businessId;
    }

    private function protected(Role $role, int $businessId): bool
    {
        return in_array($role->name, ['Admin#' . $businessId], true);
    }

    private function authorizeAbility(string $ability): void
    {
        abort_unless(auth()->user()?->can($ability), 403, 'Unauthorized action.');
    }
}
