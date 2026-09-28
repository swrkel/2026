<?php

namespace Modules\Superadmin\Http\Controllers;

use Modules\Superadmin\Entities\RefillBusiness;
use Illuminate\Http\Request;
use App\Utils\TransactionUtil;
use Illuminate\Routing\Controller;
use Yajra\DataTables\Facades\DataTables;

use Modules\Superadmin\Entities\SmsApiClient;
use Modules\Superadmin\Entities\SmsRefillPackage;
use App\Business;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\SmsLog;

class RefillBusinessController extends Controller
{
    /**
     * All Utils instance.
     *
     */
    protected $transactionUtil;

    /**
     * Constructor
     *
     * @param ProductUtils $product
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil)
    {
        $this->transactionUtil = $transactionUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!request()->ajax()) {
            return response()->noContent();
        }

        try {
            $query = RefillBusiness::query()
                ->leftJoin('sms_refill_packages', 'sms_refill_packages.id', '=', 'refill_business.package_id')
                ->leftJoin('users', 'users.id', '=', 'refill_business.created_by')
                ->leftJoin('business as refill_businesses', function ($join) {
                    $join->on('refill_businesses.id', '=', 'refill_business.business_id')
                        ->where('refill_business.type', '=', 'business');
                })
                ->leftJoin('sms_api_clients as refill_clients', function ($join) {
                    $join->on('refill_clients.id', '=', 'refill_business.business_id')
                        ->where('refill_business.type', '=', 'client');
                })
                ->select([
                    'refill_business.id',
                    'refill_business.date',
                    'refill_business.business_id',
                    'refill_business.package_id',
                    'refill_business.expiry_date',
                    'refill_business.note',
                    'refill_business.payment_method',
                    'refill_business.bank_name',
                    'refill_business.cheque_no',
                    'refill_business.cheque_date',
                    'refill_business.type',
                    'refill_business.created_by',
                    'refill_business.created_at',
                    'sms_refill_packages.name as package_name',
                    'sms_refill_packages.amount as amount',
                    'sms_refill_packages.no_of_sms as no_of_sms',
                    'users.username',
                    DB::raw("CASE
                        WHEN refill_business.type = 'business' THEN refill_businesses.name
                        WHEN refill_business.type = 'client' THEN refill_clients.name
                        ELSE ''
                    END as business_name"),
                ]);

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $query->whereDate('refill_business.date', '>=', request()->start_date)
                    ->whereDate('refill_business.date', '<=', request()->end_date);
            }

            if (!empty(request()->business_id)) {
                $query->where('refill_business.business_id', request()->business_id);

                if (!empty(request()->type)) {
                    $query->where('refill_business.type', request()->type);
                }
            }

            if (!empty(request()->package_id)) {
                $query->where('refill_business.package_id', request()->package_id);
            }

            if (!empty(request()->payment_method)) {
                $query->where('refill_business.payment_method', request()->payment_method);
            }

            if (!empty(request()->created_by)) {
                $query->where('refill_business.created_by', request()->created_by);
            }

            if (!empty(request()->business_type)) {
                $query->where('refill_business.type', request()->business_type);
            }

            $query->orderByDesc('refill_business.id');

            return DataTables::of($query)
                ->addColumn('action', function ($row) {
                    return '<div class="btn-group sms-action-dropdown">'
                        . '<button type="button" class="btn btn-info dropdown-toggle btn-xs" data-toggle="dropdown" aria-expanded="false">'
                        . e(__('messages.actions'))
                        . ' <span class="caret"></span><span class="sr-only">Toggle Dropdown</span></button>'
                        . '<ul class="dropdown-menu dropdown-menu-right" role="menu">'
                        . '<li><a href="#" data-href="' . action('\\Modules\\Superadmin\\Http\\Controllers\\RefillBusinessController@edit', [$row->id]) . '" class="btn-modal" data-container=".packages_modal"><i class="glyphicon glyphicon-edit"></i> ' . e(__('messages.edit')) . '</a></li>'
                        . '<li><a href="#" data-href="' . action('\\Modules\\Superadmin\\Http\\Controllers\\RefillBusinessController@destroy', [$row->id]) . '" class="delete_record"><i class="fa fa-trash"></i> ' . e(__('messages.delete')) . '</a></li>'
                        . '</ul></div>';
                })
                ->editColumn('type', function ($row) {
                    return e(ucfirst($row->type ?? ''));
                })
                ->editColumn('business_name', function ($row) {
                    return e($row->business_name ?? '');
                })
                ->editColumn('package_name', function ($row) {
                    return e($row->package_name ?? '');
                })
                ->editColumn('payment_method', function ($row) {
                    $paymentMethod = e($row->payment_method ?? '');

                    if (($row->payment_method ?? '') !== 'Cheque') {
                        return $paymentMethod;
                    }

                    $details = [$paymentMethod];

                    if (!empty($row->bank_name)) {
                        $details[] = '<b>' . e(__('superadmin::lang.bank_name')) . '</b>: ' . e($row->bank_name);
                    }

                    if (!empty($row->cheque_no)) {
                        $details[] = '<b>' . e(__('superadmin::lang.cheque_no')) . '</b>: ' . e($row->cheque_no);
                    }

                    if (!empty($row->cheque_date)) {
                        $details[] = '<b>' . e(__('superadmin::lang.cheque_date')) . '</b>: '
                            . e($this->transactionUtil->format_date($row->cheque_date));
                    }

                    return implode('<br>', $details);
                })
                ->editColumn('date', function ($row) {
                    $date = $row->date ?? $row->created_at ?? null;
                    return !empty($date) ? $this->transactionUtil->format_date($date) : '';
                })
                ->editColumn('expiry_date', function ($row) {
                    return !empty($row->expiry_date)
                        ? $this->transactionUtil->format_date($row->expiry_date)
                        : '';
                })
                ->editColumn('no_of_sms', function ($row) {
                    return number_format((int) ($row->no_of_sms ?? 0), 0, '.', ',');
                })
                ->editColumn('amount', function ($row) {
                    $html = $this->transactionUtil->num_f($row->amount ?? 0);

                    if (!empty($row->note)) {
                        $html .= "<br><button type='button' class='btn btn-primary btn-xs note_btn' data-string='"
                            . e($row->note)
                            . "'>"
                            . e(__('superadmin::lang.note'))
                            . '</button>';
                    }

                    return $html;
                })
                ->rawColumns(['action', 'payment_method', 'amount'])
                ->make(true);
        } catch (\Throwable $e) {
            Log::error('Refill Business DataTable failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'draw' => (int) request()->get('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
                'error' => __('messages.something_went_wrong'),
            ], 500);
        }
    }

    public function businessSMSSummary()
    {
        
        if (request()->ajax()) {
            
            $drivers = Business::select(DB::raw("'business' as type"),'business.id as id','business.name as name');
            
            $external = SmsApiClient::select(DB::raw("'client' as type"),'sms_api_clients.id as id','sms_api_clients.name as name');
            
            $data = $drivers->unionAll($external);
            
            
            return DataTables::of($data)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '<div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle btn-xs" 
                            data-toggle="dropdown" aria-expanded="false">' .
                            __("messages.actions") .
                            '<span class="caret"></span><span class="sr-only">Toggle Dropdown
                            </span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-left" role="menu">';
                        
                            $html .= '<li><a href="#" data-href="' . action('\Modules\SMS\Http\Controllers\SmsListInterestController@index', ['business_id'=>$row->id,'type' => $row->type]) . '" class="btn-modal" data-container=".view_modal"><i class="glyphicon glyphicon-edit"></i> ' . __("sms::lang.interest") . '</a></li>';
                        
                        
                        return $html;
                    }
                )
                ->editColumn('type','{{ucfirst($type)}}')
                ->addColumn('sms_balance',function($row){
                    try {
                        return $this->transactionUtil->num_f($this->transactionUtil->__getSMSBalance(date('Y-m-d'), $row->id, $row->type));
                    } catch (\Throwable $e) {
                        Log::error('SMS Summary balance failed', ['message' => $e->getMessage(), 'id' => $row->id ?? null, 'type' => $row->type ?? null]);
                        return $this->transactionUtil->num_f(0);
                    }
                })
                ->rawColumns(['action','payment_method','amount'])
                ->make(true);
        }
        
    }
    
    public function smsHistory(){
        if (request()->ajax()) {
            
            $drivers =SmsLog::orderBy('id','DESC');
            
            
            if(!empty(request()->start_date) && !empty(request()->end_date)){
                $drivers->whereDate('created_at','>=',request()->start_date)->whereDate('created_at','<=',request()->end_date);
            }
            
            if(!empty(request()->business_id) && !empty(request()->type)){
                $drivers->where('business_id',request()->business_id)->where('business_type',request()->type);
            }
            
            if(!empty(request()->business_type)){
                $drivers->where('business_type',request()->business_type);
            }
            
            if(!empty(request()->username)){
                $drivers->where('username',request()->username);
            }
            
            if(!empty(request()->sender_name)){
                $drivers->where('sender_name',request()->sender_name);
            }
            
            if(!empty(request()->sms_status)){
                $drivers->where('sms_status',request()->sms_status);
            }
            
            if(!empty(request()->sms_type_)){
                $drivers->where('sms_type_',request()->sms_type_);
            }
            
            return DataTables::of($drivers)
                ->addColumn('business_name',function($row){
                    if(($row->business_type ?? '') == 'business'){
                        $business = Business::find($row->business_id);
                    }else{
                        $business = SmsApiClient::find($row->business_id);
                    }
                    
                    return optional($business)->name ?? '';
                })
                ->editColumn('message',function($row){
                    $message = e($row->message ?? '');
                    $html = "<button class='btn btn-primary msg_btn btn-sm' data-string='".$message."'>".__('superadmin::lang.message')."</button>";
                    return $html;
                })
                ->editColumn('created_at','{{@format_datetime($created_at)}}')
                ->rawColumns(['action','message'])
                ->make(true);
        }
    }

    public function create()
    {
        $data = array();

        // S285 fix: build dropdown lists directly and predictably for modal Select2.
        // The earlier union query caused the modal to render with empty searchable results on some tenants.
        $business = Business::query()
            ->select('id', 'name', DB::raw("'business' as type"))
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->merge(
                SmsApiClient::query()
                    ->select('id', 'name', DB::raw("'client' as type"))
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->orderBy('name')
                    ->get()
            );

        $packages = SmsRefillPackage::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get();
        
        return view('superadmin::sms_refill_packages.refill_business.create')
                ->with(compact('data','business','packages'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $data = $this->validatedRefillData($request);
            $data['created_by'] = auth()->id();

            $refill = DB::transaction(function () use ($data) {
                return RefillBusiness::create($data);
            });

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
                'refill_id' => $refill->id,
                'tab' => 'refill_business',
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Refill Business create failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
                'tab' => 'refill_business',
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
        // S285 fix: use direct collections so Edit modal dropdowns always have options.
        $business = Business::query()
            ->select('id', 'name', DB::raw("'business' as type"))
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get()
            ->merge(
                SmsApiClient::query()
                    ->select('id', 'name', DB::raw("'client' as type"))
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->orderBy('name')
                    ->get()
            );
        
        $packages = SmsRefillPackage::query()
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->orderBy('name')
            ->get();
        
        $data = RefillBusiness::findOrFail($id);
        return view('superadmin::sms_refill_packages.refill_business.edit')
                ->with(compact('data','business','packages'));
    }

   

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $data = $this->validatedRefillData($request);
            $data['created_by'] = auth()->id();

            DB::transaction(function () use ($id, $data) {
                RefillBusiness::findOrFail($id)->update($data);
            });

            $output = [
                'success' => true,
                'msg' => __('lang_v1.success'),
                'tab' => 'refill_business',
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('Refill Business update failed', [
                'id' => $id,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            $output = [
                'success' => false,
                'msg' => __('messages.something_went_wrong'),
                'tab' => 'refill_business',
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
     * Validate the selected business/client, package and payment details.
     */
    private function validatedRefillData(Request $request): array
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'business_id' => ['required', 'integer'],
            'package_id' => ['required', 'integer', 'exists:sms_refill_packages,id'],
            'expiry_date' => ['required', 'date', 'after_or_equal:date'],
            'payment_method' => ['required', 'string', 'max:200'],
            'note' => ['nullable', 'string'],
            'type' => ['required', 'in:business,client'],
            'bank_name' => ['nullable', 'required_if:payment_method,Cheque', 'string', 'max:200'],
            'cheque_no' => ['nullable', 'required_if:payment_method,Cheque', 'string', 'max:200'],
            'cheque_date' => ['nullable', 'required_if:payment_method,Cheque', 'date'],
        ]);

        $businessExists = $validated['type'] === 'business'
            ? Business::whereKey($validated['business_id'])->exists()
            : SmsApiClient::whereKey($validated['business_id'])->exists();

        if (!$businessExists) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'business_id' => [__('validation.exists', ['attribute' => __('superadmin::lang.business')])],
            ]);
        }

        $isCheque = $validated['payment_method'] === 'Cheque';

        return [
            'date' => $validated['date'],
            'business_id' => (int) $validated['business_id'],
            'package_id' => (int) $validated['package_id'],
            'expiry_date' => $validated['expiry_date'],
            'note' => !empty($validated['note']) ? trim($validated['note']) : null,
            'payment_method' => $validated['payment_method'],
            'bank_name' => $isCheque ? trim($validated['bank_name']) : null,
            'cheque_no' => $isCheque ? trim($validated['cheque_no']) : null,
            'cheque_date' => $isCheque ? $validated['cheque_date'] : null,
            'type' => $validated['type'],
        ];
    }

    public function destroy($id)
    {
        try {
            
            RefillBusiness::where('id', $id)->delete();


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
