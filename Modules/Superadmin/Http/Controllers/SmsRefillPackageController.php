<?php

namespace Modules\Superadmin\Http\Controllers;

use Modules\Superadmin\Entities\SmsRefillPackage;
use Illuminate\Http\Request;
use App\Utils\ModuleUtil;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;
use App\Business;
use App\User;
use Modules\Superadmin\Entities\RefillBusiness;
use Modules\Superadmin\Entities\SmsApiClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\SmsLog;
use Modules\Superadmin\Entities\SmsReminderSetting;

class SmsRefillPackageController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil)
    {
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (request()->ajax()) {
            try {
                if (!Schema::hasTable('sms_refill_packages')) {
                    return DataTables::of(collect([]))->addColumn('action', '')->make(true);
                }

                $pkg = 'sms_refill_packages';
                $hasUsers = Schema::hasTable('users');
                $query = DB::table($pkg);

                if ($hasUsers) {
                    $query->leftJoin('users', 'users.id', '=', $pkg . '.created_by');
                }

                $select = [];
                foreach (['id', 'date', 'name', 'unit_cost', 'amount', 'no_of_sms', 'created_by', 'created_at'] as $column) {
                    $select[] = Schema::hasColumn($pkg, $column)
                        ? DB::raw($pkg . '.' . $column . ' as ' . $column)
                        : DB::raw('NULL as ' . $column);
                }

                $select[] = ($hasUsers && Schema::hasColumn('users', 'username'))
                    ? DB::raw('users.username as username')
                    : DB::raw('NULL as username');

                $rows = $query->select($select);

                if (Schema::hasColumn($pkg, 'id')) {
                    $rows->orderBy($pkg . '.id', 'desc');
                }

                $rows = $rows->get();

                return DataTables::of($rows)
                    ->addColumn('action', function ($row) {
                        if (empty($row->id)) {
                            return '';
                        }

                        return '<div class="btn-group sms-action-dropdown">'
                            . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                            . e(__('messages.actions'))
                            . ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>'
                            . '<ul class="dropdown-menu dropdown-menu-right" role="menu">'
                            . '<li><a href="#" data-href="' . action('\\Modules\\Superadmin\\Http\\Controllers\\SmsRefillPackageController@edit', [$row->id]) . '" class="sms-package-edit-trigger" data-container=".sms_package_edit_modal"><i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</a></li>'
                            . '<li><a href="#" data-href="' . action('\\Modules\\Superadmin\\Http\\Controllers\\SmsRefillPackageController@destroy', [$row->id]) . '" class="delete_record"><i class="fa fa-trash"></i> ' . e(__('messages.delete')) . '</a></li>'
                            . '</ul></div>';
                    })
                    ->editColumn('unit_cost', function ($row) {
                        return $this->moduleUtil->num_f($row->unit_cost ?? 0, false, null, false, 3);
                    })
                    ->editColumn('date', function ($row) {
                        $date = $row->date ?? $row->created_at ?? null;
                        return !empty($date) ? $this->moduleUtil->format_date($date) : '';
                    })
                    ->editColumn('name', function ($row) {
                        return e($row->name ?? '');
                    })
                    ->editColumn('no_of_sms', function ($row) {
                        return number_format((int) ($row->no_of_sms ?? 0), 0, '.', ',');
                    })
                    ->editColumn('amount', function ($row) {
                        return $this->moduleUtil->num_f($row->amount ?? 0);
                    })
                    ->editColumn('username', function ($row) {
                        return e($row->username ?? '');
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            } catch (\Throwable $e) {
                Log::error('SMS Packages DataTable failed', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);

                return response()->json([
                    'draw' => (int) request()->get('draw', 1),
                    'recordsTotal' => 0,
                    'recordsFiltered' => 0,
                    'data' => [],
                    'error' => 'SMS Packages table could not be loaded. Please check laravel.log for SMS Packages DataTable failed.'
                ]);
            }
        }

        $data = array();

        $payment_methods = Schema::hasTable('refill_business') && Schema::hasColumn('refill_business', 'payment_method')
            ? RefillBusiness::whereNotNull('payment_method')->distinct()->pluck('payment_method', 'payment_method')
            : collect([]);

        $packages = Schema::hasTable('sms_refill_packages') && Schema::hasColumn('sms_refill_packages', 'name')
            ? SmsRefillPackage::pluck('name', 'id')
            : collect([]);

        $users = Schema::hasTable('users') ? User::pluck('username', 'id') : collect([]);

        $business = collect([]);
        if (Schema::hasTable('business')) {
            $business = $business->merge(Business::select(DB::raw("'business' as type"), 'business.id as id', 'business.name as name')->get());
        }
        if (Schema::hasTable('sms_api_clients')) {
            $business = $business->merge(SmsApiClient::select(DB::raw("'client' as type"), 'sms_api_clients.id as id', 'sms_api_clients.name as name')->get());
        }

        $usernames = Schema::hasTable('sms_logs') && Schema::hasColumn('sms_logs', 'username')
            ? SmsLog::whereNotNull('username')->distinct('username')->pluck('username', 'username')
            : collect([]);
        $sender_names = Schema::hasTable('sms_logs') && Schema::hasColumn('sms_logs', 'sender_name')
            ? SmsLog::whereNotNull('sender_name')->distinct('sender_name')->pluck('sender_name', 'sender_name')
            : collect([]);
        $sms_type = Schema::hasTable('sms_logs') && Schema::hasColumn('sms_logs', 'sms_type_')
            ? SmsLog::whereNotNull('sms_type_')->distinct('sms_type_')->pluck('sms_type_', 'sms_type_')
            : collect([]);
        $sms_status = Schema::hasTable('sms_logs') && Schema::hasColumn('sms_logs', 'sms_status')
            ? SmsLog::whereNotNull('sms_status')->distinct('sms_status')->pluck('sms_status', 'sms_status')
            : collect([]);

        $templates = Schema::hasTable('sms_reminder_settings') ? SmsReminderSetting::first() : null;

        return view('superadmin::sms_refill_packages.index')
                ->with(compact('data','business','payment_methods','packages','users','usernames','sender_names','sms_type','sms_status','templates'));
    }

    public function create()
    {
        $data = array();
        return view('superadmin::sms_refill_packages.create')
                ->with(compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $this->validatedPackageData($request);
        $data['created_by'] = auth()->id();

        try {
            $package = SmsRefillPackage::create($data);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
                'package_id' => $package->id,
            ];
        } catch (\Throwable $e) {
            Log::error('SMS package create failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($output, $output['success'] ? 200 : 500);
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\SmsRefillPackageController@index')
            ->with('status', $output);
    }

    public function edit($id)
    {
        $data = SmsRefillPackage::findOrFail($id);
        return view('superadmin::sms_refill_packages.edit')
                ->with(compact('data'));
    }

   

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $this->validatedPackageData($request);
        $data['created_by'] = auth()->id();

        try {
            SmsRefillPackage::where('id', $id)->update($data);

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
            ];
        } catch (\Throwable $e) {
            Log::error('SMS package update failed', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json($output, $output['success'] ? 200 : 500);
        }

        return redirect()
            ->action('\Modules\Superadmin\Http\Controllers\SmsRefillPackageController@index')
            ->with('status', $output);
    }

    /**
     * Validate and normalize an SMS package payload.
     */
    private function validatedPackageData(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'name' => ['required', 'string', 'max:200'],
            'unit_cost' => ['required', 'numeric', 'gt:0'],
            'amount' => ['required', 'numeric', 'gte:0'],
        ]);

        $unitCost = (float) $validated['unit_cost'];
        $amount = (float) $validated['amount'];

        return [
            'date' => $validated['date'],
            'name' => trim($validated['name']),
            'unit_cost' => $unitCost,
            'amount' => $amount,
            'no_of_sms' => (int) floor($amount / $unitCost),
        ];
    }

    public function destroy($id)
    {
        try {
            
            SmsRefillPackage::where('id', $id)->delete();


            $output = [
                'success' => true,
                'msg' => __('lang_v1.success')
            ];
        } catch (\Exception $e) {
            Log::emergency('File: ' . $e->getFile() . 'Line: ' . $e->getLine() . 'Message: ' . $e->getMessage());
            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ];
        }

        return $output;
    }
}
