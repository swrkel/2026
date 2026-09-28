<?php

namespace App\Http\Middleware;

use App\Contact;
use App\Utils\SidebarPermissionUtil;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Enforces the permission that belongs to the requested action.
 *
 * The previous middleware treated view/create/update permissions as one broad
 * list per module. Consequently, owning any one permission could satisfy the
 * middleware for every URL in that module. This version determines the action
 * first (view, create, update or delete) and checks only the corresponding
 * permission alternatives.
 */
class CheckRoutePermission
{
    /**
     * @var array<string, array<string, array<int, string>>>
     */
    private const MODULE_PERMISSIONS = [
        'users' => [
            'view' => ['user.view'],
            'create' => ['user.create'],
            'update' => ['user.update'],
            'delete' => ['user.delete'],
        ],
        'roles' => [
            'view' => ['roles.view'],
            'create' => ['roles.create'],
            'update' => ['roles.update'],
            'delete' => ['roles.delete'],
        ],
        'purchases' => [
            'view' => ['purchase.entry.view', 'purchase.view'],
            'create' => ['purchase.entry.create', 'purchase.create'],
            'update' => ['purchase.entry.edit', 'purchase.update', 'purchase.update_status'],
            'delete' => ['purchase.entry.delete', 'purchase.delete'],
        ],
        'purchase-return' => [
            // The legacy return workflow is intentionally governed by the
            // existing Purchase permissions used by PurchaseReturnController.
            'view' => ['purchase.view', 'purchase.create'],
            'create' => ['purchase.update'],
            'update' => ['purchase.update'],
            'delete' => ['purchase.delete'],
        ],
        'import-purchases' => [
            // Despite the historical URL, this controller imports sales and
            // already uses the Sales permissions internally.
            'view' => ['sell.create'],
            'create' => ['sell.create'],
            'update' => ['sell.create'],
            'delete' => ['sell.delete'],
        ],
        'purchase-settings' => [
            'view' => ['purchase_settings.view'],
            'create' => ['purchase_settings.create'],
            'update' => ['purchase_settings.update'],
            'delete' => ['purchase_settings.delete'],
        ],
        'sells' => [
            'view' => ['sell.view', 'view_own_sell_only', 'direct_sell.access'],
            'create' => ['sell.create', 'direct_sell.access'],
            'update' => ['sell.update'],
            'delete' => ['sell.delete'],
        ],
        'sales' => [
            'view' => ['sell.view', 'view_own_sell_only', 'direct_sell.access'],
            'create' => ['sell.create', 'direct_sell.access'],
            'update' => ['sell.update'],
            'delete' => ['sell.delete'],
        ],
        'pos' => [
            'view' => ['sell.view', 'view_own_sell_only', 'direct_sell.access'],
            'create' => ['sell.create', 'direct_sell.access'],
            'update' => ['sell.update', 'direct_sell.access'],
            'delete' => ['sell.delete'],
        ],
        'sales-order' => [
            'view' => ['sell.view', 'view_own_sell_only'],
            'create' => ['sell.create'],
            'update' => ['sell.update'],
            'delete' => ['sell.delete'],
        ],
        'sell-return' => [
            'view' => ['sell.view', 'view_own_sell_only'],
            'create' => ['sell.update'],
            'update' => ['sell.update'],
            'delete' => ['sell.delete'],
        ],
        'contacts' => [
            'view' => ['supplier.view', 'customer.view'],
            'create' => ['supplier.create', 'customer.create'],
            'update' => ['supplier.update', 'customer.update'],
            'delete' => ['supplier.delete', 'customer.delete'],
        ],
        'customers' => [
            'view' => ['customers.access', 'customer.view'],
            'create' => ['customers.access', 'customer.create'],
            'update' => ['customers.access', 'customer.update'],
            'delete' => ['customers.access', 'customer.delete'],
        ],
        'suppliers' => [
            // Standalone Suppliers supports both the historical core supplier.*
            // permissions and the module-local suppliers.* permissions. Managed
            // UserManagementNew roles derive these module-local/core aliases from
            // their Suppliers View/Edit/Delete rights (see RolePermissionService).
            'view' => ['suppliers.access', 'supplier.view', 'suppliers.view'],
            'create' => ['suppliers.access', 'supplier.create', 'suppliers.create'],
            'update' => ['suppliers.access', 'supplier.update', 'supplier.edit', 'suppliers.edit'],
            'delete' => ['suppliers.access', 'supplier.delete', 'suppliers.delete'],
        ],
        'customer-statement' => [
            'view' => ['customer.view', 'customers.access'],
            'create' => ['customer.view', 'customers.access'],
            'update' => ['customer.update', 'customers.access'],
            'delete' => ['customer.delete', 'customers.access'],
        ],
        'customer-payment-simple' => [
            // This legacy page has one exposed role permission which governs
            // both opening the payment page and posting the payment form.
            'view' => ['payment_received.view'],
            'create' => ['payment_received.view'],
            'update' => ['payment_received.view'],
            'delete' => ['payment_received.view'],
        ],
        'products' => [
            'view' => ['product.view'],
            'create' => ['product.create'],
            'update' => ['product.update'],
            'delete' => ['product.delete'],
        ],
        'labels' => [
            'view' => ['product.view'],
            'create' => ['product.view', 'product.create'],
            'update' => ['product.update'],
            'delete' => ['product.delete'],
        ],
        'variation-templates' => [
            'view' => ['product.view'],
            'create' => ['product.create'],
            'update' => ['product.update'],
            'delete' => ['product.delete'],
        ],
        'units' => [
            'view' => ['unit.view'],
            'create' => ['unit.create'],
            'update' => ['unit.update'],
            'delete' => ['unit.delete'],
        ],
        'categories' => [
            'view' => ['category.view'],
            'create' => ['category.create'],
            'update' => ['category.update'],
            'delete' => ['category.delete'],
        ],
        'brands' => [
            'view' => ['brand.view'],
            'create' => ['brand.create'],
            'update' => ['brand.update'],
            'delete' => ['brand.delete'],
        ],
        'stock-adjustments' => [
            // Core Stock Adjustment historically uses purchase.* while the
            // separately managed Stock Adjustment permission section uses the
            // stockAdjustment.* names. Honour either exact permission without
            // allowing a view permission to authorize an edit/delete URL.
            'view' => ['purchase.view', 'purchase.create', 'stockAdjustment.list'],
            'create' => ['purchase.create', 'stockAdjustment.add'],
            'update' => ['purchase.create', 'stockAdjustment.edit'],
            'delete' => ['purchase.delete', 'stockAdjustment.delete'],
        ],
        'stock-transfers' => [
            'view' => ['purchase.view', 'purchase.create'],
            'create' => ['purchase.create'],
            'update' => ['purchase.create'],
            'delete' => ['purchase.delete'],
        ],
        'stock-settings' => [
            'view' => ['purchase.view', 'purchase.create', 'stockAdjustment.list'],
            'create' => ['purchase.create', 'stockAdjustment.add'],
            'update' => ['purchase.create', 'stockAdjustment.edit'],
            'delete' => ['purchase.delete', 'stockAdjustment.delete'],
        ],
        'expenses' => [
            'view' => ['expense.access'],
            'create' => ['expense.create'],
            'update' => ['expense.update'],
            'delete' => ['expense.delete'],
        ],
        'expense-categories' => [
            'view' => ['expense.access'],
            'create' => ['expense.access'],
            'update' => ['expense.access'],
            'delete' => ['expense.access'],
        ],
        'expense-categories-code' => [
            'view' => ['expense.access'],
            'create' => ['expense.access'],
            'update' => ['expense.access'],
            'delete' => ['expense.access'],
        ],
        'accounting-module' => [
            'view' => ['account.access', 'account.view'],
            'create' => ['account.access'],
            'update' => ['account.access'],
            'delete' => ['account.access'],
        ],
        'accounts' => [
            'view' => ['account.access', 'account.view'],
            'create' => ['account.access'],
            'update' => ['account.access'],
            'delete' => ['account.access'],
        ],
        'cheque-writing' => [
            'view' => ['enable_cheque_writing'],
            'create' => ['enable_cheque_writing'],
            'update' => ['enable_cheque_writing'],
            'delete' => ['enable_cheque_writing'],
        ],
        'petro' => [
            'view' => ['petro.access', 'enable_petro_module.access', 'pump_operator.dashboard', 'pumper_dashboard.dashboard', 'issue_customer_bill.access'],
            'create' => ['petro.access', 'pump_operator.dashboard', 'pumper_dashboard.dashboard', 'issue_customer_bill.access'],
            'update' => ['petro.access', 'pump_operator.dashboard', 'pumper_dashboard.dashboard'],
            'delete' => ['petro.access'],
        ],
        'petropd' => [
            'view' => ['petropd.access', 'petro_pd.access', 'pump_operator.dashboard', 'pump_operator.main_system'],
            'create' => ['petropd.access', 'petro_pd.access', 'pump_operator.dashboard'],
            'update' => ['petropd.access', 'petro_pd.access', 'pump_operator.main_system'],
            'delete' => ['petropd.access', 'petro_pd.access'],
        ],
        'mpcs' => [
            'view' => ['mpcs.access'],
            'create' => ['mpcs.access'],
            'update' => ['mpcs.access'],
            'delete' => ['mpcs.access'],
        ],
        'distribution' => [
            'view' => ['distribution_module.access'],
            'create' => ['distribution_module.access'],
            'update' => ['distribution_module.access'],
            'delete' => ['distribution_module.access'],
        ],
        'dashboard-logistics' => [
            'view' => ['dashboard_logistics.access', 'dashboard.data'],
            'create' => ['dashboard_logistics.access'],
            'update' => ['dashboard_logistics.access'],
            'delete' => ['dashboard_logistics.access'],
        ],
    ];

    /** @var array<string, string> */
    private const SEGMENT_ALIASES = [
        'purchase-pos' => 'purchases',
        'purchase-settings' => 'purchase-settings',
        'import-purchases' => 'import-purchases',
        'sell-return' => 'sell-return',
        'sales-order' => 'sales-order',
        'customer-payment' => 'customer-payment-simple',
        'customer-payment-simple' => 'customer-payment-simple',
        'expense-categories-number' => 'expense-categories-code',
        'expense-category-codes' => 'expense-categories-code',
        'sales-commission-agents' => 'users',
        'account' => 'accounts',
        'cheque-templates' => 'cheque-writing',
        'cheque-write' => 'cheque-writing',
        'cheque-numbers' => 'cheque-writing',
        'cheque-dashboard' => 'cheque-writing',
        'stamps' => 'cheque-writing',
        'printed_cheque_details' => 'cheque-writing',
        'deleted_cheque_details' => 'cheque-writing',
    ];

    /** @var array<string, string> */
    private const CONTROLLER_MODULES = [
        'ManageUserController' => 'users',
        'RoleController' => 'roles',
        'PurchaseController' => 'purchases',
        'PurchaseReturnController' => 'purchase-return',
        'ExpenseController' => 'expenses',
        'ExpenseCategoryController' => 'expense-categories',
        'ContactController' => 'contacts',
        'ProductController' => 'products',
        'UnitController' => 'units',
        'CategoryController' => 'categories',
        'BrandController' => 'brands',
        'StockAdjustmentController' => 'stock-adjustments',
        'StockTransferController' => 'stock-transfers',
        'DashboardLogisticsController' => 'dashboard-logistics',
    ];

    /**
     * Methods in a permission-managed controller that are personal session
     * utilities rather than module administration pages.
     *
     * @var array<string, array<int, string>>
     */
    private const CONTROLLER_METHOD_EXCLUSIONS = [
        'ManageUserController' => [
            'lockScreen',
            'checkUserPassword',
        ],
    ];

    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        $businessId = $request->hasSession()
            ? (int) ($request->session()->get('business.id')
                ?: $request->session()->get('user.business_id'))
            : (int) ($user->business_id ?? 0);

        /*
         * User Management New owns Roles when enabled for this business.
         * Redirect the legacy /roles entry before legacy user_management
         * permission/module classification can run.
         */
        $requestPath = trim((string) $request->path(), '/');
        if (in_array(strtoupper((string) $request->method()), ['GET', 'HEAD'], true)
            && $requestPath === 'roles'
            && $businessId > 0
            && SidebarPermissionUtil::isManageSidebarEnabled('user_management_new', $businessId)
            && \Illuminate\Support\Facades\Route::has('user-management-new.roles.index')) {
            return redirect()->route('user-management-new.roles.index');
        }

        if (SidebarPermissionUtil::hasSuperAdminBypass()
            || ($businessId > 0 && $user->hasRole('Admin#' . $businessId))) {
            \Log::info('JRN bypass taken', ['url' => $request->path()]);
            $r = $next($request);
            \Log::info('JRN returned', ['status' => method_exists($r,"getStatusCode") ? $r->getStatusCode() : "?"]);
            return $r;
        }

        // Managed User Roles are authoritative. Remove historical direct
        // functional grants before any Blade/Gate checks can reuse them. Admin
        // and genuine Super Admin accounts have already bypassed above.
        $this->enforceRoleBoundaryOncePerSession($request, $user, $businessId);

        $route = $request->route();
        $action = $route ? (array) $route->getAction() : [];
        $explicitPermission = $action['permission'] ?? $action['permissions'] ?? null;

        if (! empty($explicitPermission)) {
            return $this->allowAny($user, $explicitPermission)
                ? $next($request)
                : $this->deny($request);
        }

        $permissions = $this->inferPermissions($request, $action);
        if ($permissions !== [] && ! $this->allowAny($user, $permissions)) {
            return $this->deny($request);
        }

        // Some standalone/legacy routes have no explicit or inferable Spatie
        // permission. For UserManagementNew-managed users, the module grant is
        // still mandatory. This is a fail-closed fallback; the module's own
        // managed-role middleware continues to enforce page/right detail.
        if (SidebarPermissionUtil::usesManagedRoleForCurrentUser($businessId)) {
            foreach (SidebarPermissionUtil::routeModuleKeysForRequest($request) as $moduleKey) {
                if (!SidebarPermissionUtil::isModuleAllowedForCurrentManagedRole($moduleKey, $businessId)) {
                    return $this->deny($request);
                }
            }
        }

        return $next($request);
    }

    /** @param mixed $permissions */
    protected function allowAny($user, $permissions): bool
    {
        $permissions = is_array($permissions)
            ? $permissions
            : preg_split('/[|,]/', (string) $permissions);

        foreach ((array) $permissions as $permission) {
            $permission = trim((string) $permission);
            if ($permission === '') {
                continue;
            }

            if (method_exists($user, 'roleAllowsPermission')) {
                if ($user->roleAllowsPermission($permission)) {
                    return true;
                }
                continue;
            }

            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    }

    private function enforceRoleBoundaryOncePerSession(Request $request, $user, int $businessId): void
    {
        if (!method_exists($user, 'retainOnlyRoleScopedDirectPermissions') || !$request->hasSession()) {
            return;
        }

        $sessionKey = 'strict_role_permissions_v2_user_' . (int) $user->id
            . '_business_' . $businessId;
        if ($request->session()->get($sessionKey) === 1) {
            return;
        }

        // Only set the marker when this is actually a managed role. Legacy
        // users remain eligible for cleanup if they are later assigned one.
        if ($user->retainOnlyRoleScopedDirectPermissions($businessId)) {
            $request->session()->put($sessionKey, 1);
        }
    }

    /** @return array<int, string> */
    protected function inferPermissions(Request $request, array $action): array
    {
        [$controllerClass, $controllerMethod] = $this->controllerParts((string) ($action['controller'] ?? ''));

        if ($controllerClass !== ''
            && in_array($controllerMethod, self::CONTROLLER_METHOD_EXCLUSIONS[$controllerClass] ?? [], true)) {
            return [];
        }

        $module = $this->resolveModule($request, $controllerClass);
        if ($module === '' || ! isset(self::MODULE_PERMISSIONS[$module])) {
            return [];
        }

        $operation = $this->resolveOperation($request, $controllerMethod);
        $permissions = self::MODULE_PERMISSIONS[$module][$operation] ?? [];

        // Create/edit screens frequently call read-only AJAX helpers such as
        // getProductRow or searchCustomers. Those endpoints need to work for a
        // user who owns the corresponding create/update permission, while main
        // index/show/report pages remain protected by the strict view mapping.
        if ($operation === 'view' && $this->isAuxiliaryRead($request, $controllerMethod)) {
            $permissions = array_merge(
                $permissions,
                self::MODULE_PERMISSIONS[$module]['create'] ?? [],
                self::MODULE_PERMISSIONS[$module]['update'] ?? []
            );
        }

        // Generic contacts routes carry the actual entity type in the query or
        // form payload. Narrow the alternatives so supplier permission cannot
        // authorize a customer URL, or vice versa.
        if ($module === 'contacts') {
            $type = $this->resolveContactType($request, $controllerMethod);
            if ($type === 'customer') {
                $permissions = $this->filterPrefix($permissions, 'customer.');
            } elseif ($type === 'supplier') {
                $permissions = $this->filterPrefix($permissions, 'supplier.');
            }
        }

        return array_values(array_unique(array_filter($permissions)));
    }

    private function resolveModule(Request $request, string $controllerClass): string
    {
        $segment = strtolower((string) $request->segment(1));
        $segment = self::SEGMENT_ALIASES[$segment] ?? $segment;

        if (isset(self::MODULE_PERMISSIONS[$segment])) {
            return $segment;
        }

        return self::CONTROLLER_MODULES[$controllerClass] ?? '';
    }

    private function resolveOperation(Request $request, string $controllerMethod): string
    {
        $method = strtolower($controllerMethod);
        $routeName = strtolower((string) optional($request->route())->getName());
        $path = strtolower(trim($request->path(), '/'));
        $haystack = implode(' ', [$method, $routeName, $path]);

        if ($request->isMethod('DELETE')
            || $this->startsWithAny($method, ['destroy', 'delete', 'remove', 'purge', 'forceDelete'])
            || preg_match('/(?:^|[._\/-])(destroy|delete|remove|purge|force-delete)(?:$|[._\/-])/', $haystack)) {
            return 'delete';
        }

        if ($request->isMethod('PUT')
            || $request->isMethod('PATCH')
            || $this->startsWithAny($method, [
                'edit', 'update', 'change', 'toggle', 'activate', 'deactivate',
                'approve', 'reject', 'restore', 'finalize', 'settle', 'assign',
                'unassign', 'sync', 'lock', 'unlock', 'adjust', 'status',
            ])
            || preg_match('/(?:^|[._\/-])(edit|update|change|toggle|activate|deactivate|approve|reject|restore|finalize|settle|assign|unassign|sync|lock|unlock|adjust|status)(?:$|[._\/-])/', $haystack)) {
            return 'update';
        }

        if ($this->startsWithAny($method, ['create', 'store', 'add', 'import', 'upload', 'duplicate', 'clone', 'generate'])
            || preg_match('/(?:^|[._\/-])(create|store|add|import|upload|duplicate|clone|generate)(?:$|[._\/-])/', $haystack)) {
            return 'create';
        }

        if ($this->startsWithAny($method, [
            'index', 'show', 'list', 'get', 'search', 'fetch', 'load', 'view',
            'print', 'export', 'download', 'report', 'statement', 'ledger',
            'details', 'data', 'datatable', 'lookup', 'check', 'preview',
        ])) {
            return 'view';
        }

        // POST is intentionally evaluated after method/name patterns. Many ERP
        // lookup and DataTable endpoints use POST even though they are read-only.
        if ($request->isMethod('POST')) {
            return 'create';
        }

        return 'view';
    }


    private function isAuxiliaryRead(Request $request, string $controllerMethod): bool
    {
        $method = strtolower($controllerMethod);
        $routeName = strtolower((string) optional($request->route())->getName());
        $path = strtolower(trim($request->path(), '/'));

        if ($this->startsWithAny($method, [
            'index', 'show', 'list', 'view', 'print', 'export', 'download',
            'report', 'statement', 'ledger', 'details', 'datatable',
        ])) {
            return false;
        }

        $helperMethod = $this->startsWithAny($method, [
            'get', 'search', 'fetch', 'load', 'lookup', 'check', 'preview',
            'calculate', 'validate', 'autocomplete', 'options',
        ]);
        $helperRoute = preg_match(
            '/(?:^|[._\/-])(get|search|fetch|load|lookup|check|preview|calculate|validate|autocomplete|options)(?:$|[._\/-])/',
            $routeName . ' ' . $path
        ) === 1;

        return $helperMethod || $helperRoute || $request->ajax() || $request->expectsJson();
    }

    /** @param array<int, string> $prefixes */
    private function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, strtolower($prefix))) {
                return true;
            }
        }

        return false;
    }

    /** @return array{0:string,1:string} */
    private function controllerParts(string $controller): array
    {
        if ($controller === '' || ! str_contains($controller, '@')) {
            return ['', ''];
        }

        [$class, $method] = explode('@', $controller, 2);
        $class = str_replace('\\', '/', $class);
        $class = basename($class);

        return [$class, $method];
    }

    /**
     * Resolve the actual contact entity for resource URLs that do not carry a
     * type query parameter. Without this lookup, supplier.update could satisfy
     * a customer edit URL (and vice versa) because the legacy controller checks
     * the two permissions as one broad OR condition.
     */
    private function resolveContactType(Request $request, string $controllerMethod): string
    {
        $type = strtolower(trim((string) ($request->input('type') ?: $request->query('type'))));
        if (in_array($type, ['customer', 'supplier', 'both'], true)) {
            return $type;
        }

        if (! in_array(strtolower($controllerMethod), ['show', 'edit', 'update', 'destroy'], true)) {
            return '';
        }

        try {
            $route = $request->route();
            $routeValue = null;
            if (is_object($route) && method_exists($route, 'parameter')) {
                $routeValue = $route->parameter('contact');
                if ($routeValue === null) {
                    $routeValue = $route->parameter('id');
                }
            }

            if (is_object($routeValue) && isset($routeValue->type)) {
                return strtolower((string) $routeValue->type);
            }

            $contactId = is_scalar($routeValue) ? (int) $routeValue : 0;
            if ($contactId <= 0) {
                $segments = array_values(array_filter(explode('/', trim($request->path(), '/')), 'strlen'));
                foreach ($segments as $segment) {
                    if (ctype_digit((string) $segment)) {
                        $contactId = (int) $segment;
                        break;
                    }
                }
            }

            if ($contactId <= 0) {
                return '';
            }

            $businessId = $request->hasSession()
                ? (int) ($request->session()->get('business.id')
                    ?: $request->session()->get('user.business_id'))
                : (int) (auth()->user()->business_id ?? 0);

            if ($businessId <= 0) {
                return '';
            }

            return strtolower((string) Contact::query()
                ->where('business_id', $businessId)
                ->whereKey($contactId)
                ->value('type'));
        } catch (Throwable $e) {
            // Existing controller checks remain the fallback. Permission
            // resolution must not turn a transient lookup problem into a 500.
            return '';
        }
    }

    /** @param array<int, string> $permissions
     *  @return array<int, string>
     */
    private function filterPrefix(array $permissions, string $prefix): array
    {
        return array_values(array_filter(
            $permissions,
            static fn (string $permission): bool => str_starts_with($permission, $prefix)
        ));
    }

    private function deny(Request $request): Response
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action.',
            ], 403);
        }

        abort(403, 'Unauthorized action.');
    }
}
