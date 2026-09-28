<?php

namespace Modules\Superadmin\Http\Controllers;

use App\Tenant;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\TenantDatabaseManager;
use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager;
use Yajra\DataTables\Facades\DataTables;

class TenantManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        if (request()->ajax()) {
            $tenants = Tenant::select('*');

            return Datatables::of($tenants)
                ->addColumn(
                    'action',
                    '<button data-href="{{ action(\'\Modules\Superadmin\Http\Controllers\TenantManagementController@destroy\', [$id]) }}" class="btn btn-xs btn-danger delete_tenant_button"><i class="glyphicon glyphicon-trash"></i> @lang("messages.delete")</button>'
                )
                ->editColumn('created_at', '{{ @format_date($created_at) }}')
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('superadmin::tenant_management.index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('superadmin::tenant_management.create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $centralConnection = config('tenancy.database.central_connection', config('database.default'));
        $tenantInput = trim($request->input('name'));
        $tenantDomain = $this->normalizeTenantDomain($tenantInput);
        $tenantId = $this->tenantIdFromInput($tenantInput);
        $databaseName = config('tenancy.database.prefix') . $tenantId . config('tenancy.database.suffix');

        if (! preg_match('/^[a-z0-9][a-z0-9-]*[a-z0-9]$/', $tenantId)) {
            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('validation.alpha_dash', ['attribute' => __('superadmin::lang.tenant_name')]),
            ]);
        }

        $existingTenant = Tenant::on($centralConnection)->where('id', $tenantId)->first();
        $existingDomain = Domain::on($centralConnection)->where('domain', $tenantDomain)->first();

        if ($existingDomain && $existingDomain->tenant_id !== $tenantId) {
            return redirect()->back()->withInput()->with('status', [
                'success' => false,
                'msg' => __('validation.unique', ['attribute' => 'domain']),
            ]);
        }

        DB::connection($centralConnection)->beginTransaction();

       try {

            if ($existingTenant) {
                $tenant = $existingTenant;
                $data = $tenant->data ?? [];
                if (empty($data['tenancy_db_name'])) {
                    $data['tenancy_db_name'] = $databaseName;
                    $tenant->data = $data;
                    $tenant->save();
                }
            } else {
                $tenant = Tenant::on($centralConnection)->create([
                    'id' => $tenantId,
                    'data' => [
                        'tenancy_db_name' => $databaseName,
                    ],
                ]);
            }

            if (! $existingDomain) {
                $tenant->domains()->create([
                    'id' => $this->nextDomainId($centralConnection),
                    'domain' => $tenantDomain,
                ]);
            }

            DB::connection($centralConnection)->commit();

            $output = [
                'success' => true,
                'msg' => __('superadmin::lang.tenant_create_success')
            ];
        } catch (\Exception $e) {
            DB::connection($centralConnection)->rollBack();
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return redirect()->back()->with('status', $output);
    }

    private function tenantIdFromInput(string $input): string
    {
        $host = $this->hostFromInput($input);
        $centralDomain = $this->normalizeHostname((string) config('tenancy.central_domains.0'));

        if ($centralDomain && Str::endsWith($host, '.' . $centralDomain)) {
            $host = Str::beforeLast($host, '.' . $centralDomain);
        }

        $label = Str::contains($host, '.') ? Str::before($host, '.') : $host;

        return Str::slug($label);
    }

    private function normalizeTenantDomain(string $input): string
    {
        $host = $this->hostFromInput($input);
        $centralDomain = $this->normalizeHostname((string) config('tenancy.central_domains.0'));

        if (Str::contains($host, '.')) {
            return $host;
        }

        return Str::slug($host) . ($centralDomain ? '.' . $centralDomain : '');
    }

    private function hostFromInput(string $input): string
    {
        $input = trim(Str::lower($input));
        $input = preg_replace('#^https?://#', '', $input);
        $input = Str::before($input, '/');
        $input = Str::before($input, ':');

        return $this->normalizeHostname($input);
    }

    private function normalizeHostname(string $host): string
    {
        $host = trim(Str::lower($host));
        $host = trim($host, " \t\n\r\0\x0B.");
        $host = preg_replace('/\s+/', '-', $host);
        $host = preg_replace('/[^a-z0-9.-]/', '-', $host);
        $host = preg_replace('/-+/', '-', $host);

        return $host;
    }

    private function nextDomainId(string $centralConnection): int
    {
        return ((int) DB::connection($centralConnection)
            ->table('domains')
            ->lockForUpdate()
            ->max('id')) + 1;
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('superadmin::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('superadmin::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        try {
            Tenant::where('id', $id)->delete();

            $output = [
                'success' => true,
                'msg' => __("superadmin::lang.tenant_delete_success")
            ];
        } catch (\Exception $e) {
            Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = [
                'success' => false,
                'msg' => __("messages.something_went_wrong")
            ];
        }

        return  $output;
    }
}
