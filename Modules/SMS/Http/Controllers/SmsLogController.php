<?php

namespace Modules\SMS\Http\Controllers;

use App\Member;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Member\Entities\Balamandalaya;
use Modules\Member\Entities\GramasevaVasama;
use Modules\SMS\Entities\SmsList;
use Yajra\DataTables\Facades\DataTables;

use App\SmsLog;

class SmsLogController extends Controller
{
    protected $businessUtil;
    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param Util $businessUtil
     * @return void
     */
    public function __construct(BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->businessUtil = $businessUtil;
        $this->moduleUtil =  $moduleUtil;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        $business_id = $this->currentBusinessId();

        if (!request()->ajax()) {
            return response()->noContent();
        }

        $model = new SmsLog();
        $table = $model->getTable();

        // Return a valid empty DataTable payload rather than allowing a missing
        // legacy table/column to turn the Delivery Report into an AJAX error.
        if ($business_id <= 0 || !Schema::hasTable($table) || !Schema::hasColumn($table, 'business_id')) {
            return DataTables::of(collect())->make(true);
        }

        $drivers = SmsLog::query()
            ->where('business_id', $business_id)
            ->orderBy('id', 'DESC');

        if (!empty(request()->start_date) && !empty(request()->end_date) && Schema::hasColumn($table, 'created_at')) {
            $drivers->whereDate('created_at', '>=', request()->start_date)
                ->whereDate('created_at', '<=', request()->end_date);
        }

        if (!empty(request()->username) && Schema::hasColumn($table, 'username')) {
            $drivers->where('username', request()->username);
        }

        if (!empty(request()->sender_name) && Schema::hasColumn($table, 'sender_name')) {
            $drivers->where('sender_name', request()->sender_name);
        }

        if (!empty(request()->sms_status) && Schema::hasColumn($table, 'sms_status')) {
            $drivers->where('sms_status', request()->sms_status);
        }

        if (!empty(request()->sms_type_)) {
            if (Schema::hasColumn($table, 'sms_type_')) {
                $drivers->where('sms_type_', request()->sms_type_);
            } elseif (Schema::hasColumn($table, 'sms_type')) {
                $drivers->where('sms_type', request()->sms_type_);
            }
        }

        return DataTables::of($drivers)
            ->editColumn('message', function ($row) {
                $message = (string) ($row->message ?? '');
                return "<button type='button' class='btn btn-primary msg_btn btn-sm' data-string='" . e($message) . "'>" . __('superadmin::lang.message') . "</button>";
            })
            ->addColumn('sms_type_display', function ($row) {
                $primary = trim((string) ($row->sms_type ?? ''));
                $secondary = trim((string) ($row->sms_type_ ?? ''));

                if ($primary !== '' && $secondary !== '' && strcasecmp($primary, $secondary) !== 0) {
                    return $primary . ' / ' . $secondary;
                }

                return $primary !== '' ? $primary : ($secondary !== '' ? $secondary : '-');
            })
            ->editColumn('sender_name', fn ($row) => trim((string) ($row->sender_name ?? '')) ?: '-')
            ->editColumn('recipient', fn ($row) => trim((string) ($row->recipient ?? '')) ?: '-')
            ->editColumn('no_of_sms', fn ($row) => $row->no_of_sms ?? '-')
            ->editColumn('sms_status', fn ($row) => trim((string) ($row->sms_status ?? '')) ?: '-')
            ->editColumn('created_at', function ($row) {
                if (empty($row->created_at)) {
                    return '-';
                }

                try {
                    return function_exists('format_datetime')
                        ? format_datetime($row->created_at)
                        : (string) $row->created_at;
                } catch (\Throwable $e) {
                    return (string) $row->created_at;
                }
            })
            ->rawColumns(['message'])
            ->make(true);
    }

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
        return view('sms::edit');
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

    
}
