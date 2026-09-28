<?php

namespace Modules\HelpGuide\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HelpGuide\Services\ModuleAssignmentService;
use Modules\HelpGuide\Services\TenantDatabaseService;
use Modules\HelpGuide\Services\VisibilityService;

class SuperadminModuleController extends Controller
{
    public function __construct(
        private TenantDatabaseService $databases,
        private ModuleAssignmentService $assignments,
        private VisibilityService $visibility
    ) {}

    private function authorizeSuperadmin(): void
    {
        if (!auth()->check() || !auth()->user()->can('superadmin')) {
            abort(403, 'Unauthorized action.');
        }
    }

    public function index()
    {
        $this->authorizeSuperadmin();
        return view('helpguide::admin.modules', [
            'tenants' => $this->databases->tenantRows(),
            'ready' => $this->visibility->ready(),
        ]);
    }

    public function businesses(Request $request)
    {
        $this->authorizeSuperadmin();
        $tenantId = (string) $request->query('tenant_id', '');
        if ($tenantId === '' || $tenantId === 'all') {
            $rows = [];
            foreach ($this->databases->tenantRows() as $tenant) {
                try {
                    $connection = $this->databases->tenantConnection($tenant['database']);
                    foreach ($this->assignments->businesses($connection) as $business) {
                        $business['tenant_id'] = $tenant['id'];
                        $business['tenant_database'] = $tenant['database'];
                        $rows[] = $business;
                    }
                } catch (\Throwable $e) {
                    report($e);
                }
            }
            return response()->json(['businesses' => $rows]);
        }
        $database = $this->databases->tenantDatabase($tenantId);
        if (!$database) return response()->json(['businesses' => []]);
        $connection = $this->databases->tenantConnection($database);
        $rows = $this->assignments->businesses($connection);
        foreach ($rows as &$business) {
            $business['tenant_id'] = $tenantId;
            $business['tenant_database'] = $database;
        }
        return response()->json(['businesses' => $rows]);
    }

    public function matrix(Request $request)
    {
        $this->authorizeSuperadmin();
        $tenantId = (string) $request->query('tenant_id', '');
        $businessId = (string) $request->query('business_id', '');
        if ($tenantId === '' || $tenantId === 'all' || $businessId === '' || $businessId === 'all') {
            return response()->json(['error' => 'Select one Tenant Database and one Business to edit Help Guide module visibility.'], 422);
        }
        $database = $this->databases->tenantDatabase($tenantId);
        if (!$database) return response()->json(['error' => 'Tenant database not found.'], 404);
        $connection = $this->databases->tenantConnection($database);
        $businesses = collect($this->assignments->businesses($connection));
        $business = $businesses->firstWhere('id', (int) $businessId);
        if (!$business) return response()->json(['error' => 'Business not found in selected tenant database.'], 404);

        $assigned = $this->assignments->assignment($connection, (int) $businessId);
        $helpEnabled = $this->visibility->resolve($tenantId, $database, $business, $assigned);
        $catalog = $this->assignments->catalog();
        $rows = [];
        foreach ($catalog as $key => $meta) {
            $rows[] = [
                'key' => $key,
                'name' => $meta['name'],
                'assigned' => !empty($assigned[$key]),
                'help_enabled' => !empty($helpEnabled[$key]),
            ];
        }
        return response()->json([
            'business' => $business,
            'tenant_id' => $tenantId,
            'tenant_database' => $database,
            'modules' => $rows,
        ]);
    }

    public function save(Request $request)
    {
        $this->authorizeSuperadmin();
        $data = $request->validate([
            'tenant_id' => ['required', 'string'],
            'business_id' => ['required', 'integer'],
            'enabled_modules' => ['nullable', 'array'],
            'enabled_modules.*' => ['nullable'],
        ]);
        $tenantId = (string) $data['tenant_id'];
        $database = $this->databases->tenantDatabase($tenantId);
        if (!$database) return response()->json(['success' => false, 'message' => 'Tenant database not found.'], 404);
        $connection = $this->databases->tenantConnection($database);
        $business = collect($this->assignments->businesses($connection))->firstWhere('id', (int) $data['business_id']);
        if (!$business) return response()->json(['success' => false, 'message' => 'Business not found.'], 404);
        $assigned = $this->assignments->assignment($connection, (int) $data['business_id']);
        $enabled = array_fill_keys(array_keys($request->input('enabled_modules', [])), true);
        $this->visibility->save($tenantId, $database, $business, $assigned, $enabled);
        return response()->json(['success' => true, 'message' => 'Help Guide module visibility saved successfully.']);
    }
}
