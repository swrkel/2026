<?php

namespace Modules\SMS\Http\Controllers;

use App\Member;
use App\Utils\TransactionUtil;
use App\Utils\ModuleUtil;
;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

use Yajra\DataTables\Facades\DataTables;

use Modules\SMS\Entities\SmsListInterest;
use Modules\Superadmin\Entities\RefillBusiness;
use Illuminate\Support\Facades\DB;
use App\SmsLog;


class SmsLedger extends Controller
{
    protected $transactionUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param Util $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ModuleUtil $moduleUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil =  $moduleUtil;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $start_date = request()->start_date ?? date('Y-m-d');
        $end_date = request()->end_date ?? date('Y-m-d');
        
        $ledger_details = $this->transactionUtil->__getSMSLedger($start_date, $end_date);
        $bf_bal = $this->transactionUtil->__getSMSBFBalance($start_date);
        
        return view('sms::ledger.ledger')->with(compact(
            'ledger_details','bf_bal'
        ));
    }
    
    public function viewLedger(){
        return view('sms::ledger.index');
    }
    
    public function smsDelivery()
    {
        $business_id = $this->currentBusinessId();
        $sender_names = collect();
        $sms_status = collect();

        // S773: tenant logins are not consistent about which of these two
        // session keys is populated.  A null business id made the report look
        // completely empty even when sms_logs already contained records.
        if ($business_id > 0) {
            $model = new SmsLog();
            $table = $model->getTable();

            if (Schema::hasTable($table) && Schema::hasColumn($table, 'business_id')) {
                if (Schema::hasColumn($table, 'sender_name')) {
                    $sender_names = SmsLog::where('business_id', $business_id)
                        ->whereNotNull('sender_name')
                        ->where('sender_name', '!=', '')
                        ->distinct()
                        ->orderBy('sender_name')
                        ->pluck('sender_name', 'sender_name');
                }

                if (Schema::hasColumn($table, 'sms_status')) {
                    $sms_status = SmsLog::where('business_id', $business_id)
                        ->whereNotNull('sms_status')
                        ->where('sms_status', '!=', '')
                        ->distinct()
                        ->orderBy('sms_status')
                        ->pluck('sms_status', 'sms_status');
                }
            }
        }

        return view('sms::ledger.sms_delivery')->with(compact('sender_names', 'sms_status'));
    }

    /**
     * Resolve the current business safely for both the legacy SMS session key
     * and the newer tenant/business login context.
     */
    protected function currentBusinessId(): int
    {
        return (int) (
            request()->session()->get('business.id')
            ?: request()->session()->get('user.business_id')
            ?: optional(auth()->user())->business_id
            ?: 0
        );
    }
    
    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        
    }

    /**
     * Store a newly created resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        
    }

    /**
     * Show the specified resource.
     * @return Response
     */
    public function show($id)
    {
        
    }

    /**
     * Show the form for editing the specified resource.
     * @return Response
     */
    public function edit()
    {
        
    }

    /**
     * Update the specified resource in storage.
     * @param  Request $request
     * @return Response
     */
    public function update(Request $request)
    {
    }

    /**
     * Remove the specified resource from storage.
     * @return Response
     */
    public function destroy()
    {
    }

    /**
     * View the specified resource from storage.
     * @return Response
     */
    
}
