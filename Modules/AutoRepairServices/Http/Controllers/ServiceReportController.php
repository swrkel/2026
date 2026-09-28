<?php

namespace Modules\AutoRepairServices\Http\Controllers;
use App\Services\Documents\GlobalPdfService;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use App\Contact;
use App\Brands;
use App\BusinessLocation;
use App\Business;
use App\Category;
use App\CustomerReference;
use Modules\AutoRepairServices\Entities\DeviceModel;
use Modules\AutoRepairServices\Entities\RepairStatus;
use Modules\AutoRepairServices\Utils\RepairUtil;
use App\Utils\Util;
use Modules\AutoRepairServices\Entities\JobSheet;
use App\Utils\CashRegisterUtil;
use Yajra\DataTables\Facades\DataTables;
use DB;
use App\Utils\ModuleUtil;
use App\CustomerGroup;
use App\Utils\ContactUtil;
use App\Utils\ProductUtil;
use App\Media;
use Spatie\Activitylog\Models\Activity;
use App\new_vehicle;
use App\Utils\BusinessUtil;
use App\Utils\NotificationUtil;
use App\Transaction;
use Illuminate\Support\Facades\Log;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Entities\Package;
class ServiceReportController extends Controller
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
    public function __construct( RepairUtil $repairUtil)
    {
       
        $this->repairUtil = $repairUtil;
    }

    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
{
    
    $business_id = request()->session()->get('user.business_id');
    $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name')->first();
    $business_details = Business::find($business_id);
    $subscription = Subscription::active_subscription($business_id);
    $whatsapp_phone_no = 0;
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
        $whatsapp_phone_no = $pacakge_details['whatsapp_phone_no']??0;
    } 
    
    // Get currency precision from business settings or default to 2
    $currency_precision = !empty($business_details) && !empty($business_details->currency_precision) 
        ? $business_details->currency_precision 
        : config('constants.currency_precision', 2);

    if (request()->ajax()) {
        try {
            // Base query
            $querys = Transaction::leftJoin('repair_job_sheets', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                ->leftJoin('contacts', 'repair_job_sheets.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->leftJoin('repair_device_models', 'repair_job_sheets.vehicle_id', '=', 'repair_device_models.id')
                ->leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
                ->whereNotNull('transactions.repair_job_sheet_id')
                ->where('transactions.business_id', $business_id)
                ->select([
                    'transactions.invoice_no as bill_no',
                    'contacts.name as customer',
                    'repair_device_models.name as vehicle_name',
                    'repair_job_sheets.service_type as service_type',
                    'repair_job_sheets.job_sheet_no as job_sheet_no',
                    'repair_job_sheets.service_staff as service_staff',
                    'transaction_payments.note as note',
                    'transaction_payments.method as method',

                    // Apply currency precision using ROUND()
                    \DB::raw("ROUND(transaction_payments.amount, $currency_precision) as final_total"),
                    \DB::raw("ROUND(CASE WHEN transaction_payments.method = 'cash' THEN transaction_payments.amount ELSE 0 END, $currency_precision) as cash"),
                    \DB::raw("ROUND(CASE WHEN transaction_payments.method = 'card' THEN transaction_payments.amount ELSE 0 END, $currency_precision) as card"),
                    \DB::raw("ROUND(CASE WHEN transactions.is_credit_sale = 1 THEN transactions.final_total ELSE 0 END, $currency_precision) as final_total")
                ])
                
                ->orderBy('transactions.id', 'desc');
    
            // Apply date filter if provided
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $querys->whereDate('transactions.transaction_date', '>=', request()->start_date)
                       ->whereDate('transactions.transaction_date', '<=', request()->end_date);
            }

            // Fetch data
           
            $querys = $querys->get();

            // Return data for DataTable
            return datatables()->of($querys)
                ->removeColumn('id')  
                ->make(true);

        } catch (\Exception $e) {
            // Log the error with the exception message
            Log::error('Error occurred in service report index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Return a response indicating an error
            return response()->json([
                'error' => 'Something went wrong while fetching data.'
            ], 500);
        }
    }

    return view('autorepairservices::servicereport.index', compact('business_locations','whatsapp_phone_no'));
}
public function selesDetailreportdaily()
{
    $business_id = request()->session()->get('user.business_id');
    $business_details = Business::find($business_id);
    $currency_precision = $business_details->currency_precision ?? config('constants.currency_precision', 2);

    $date_today = now()->toDateString(); // Get today's date

    try {
        // Fetch total sales
        $totals = Transaction::leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
        ->where('transactions.business_id', $business_id)
        ->where('transactions.type', 'sell')         
        ->whereIn('transactions.status', ['final', 'order'])
        ->whereDate('transactions.transaction_date', $date_today)
        ->selectRaw("
            transactions.invoice_no,
            ROUND(COALESCE(SUM(CASE WHEN transaction_payments.method = 'cash' THEN transaction_payments.amount ELSE 0 END), 0), ?) as total_cash,
            ROUND(COALESCE(SUM(CASE WHEN transactions.is_credit_sale = 1 THEN transactions.final_total ELSE 0 END), 0), ?) as total_credit,
            ROUND(COALESCE(SUM(CASE WHEN transaction_payments.method = 'card' THEN transaction_payments.amount ELSE 0 END), 0), ?) as total_card,
            ROUND(COALESCE(SUM(transaction_payments.amount), 0), ?) as total_sales
        ", array_fill(0, 4, $currency_precision))
        ->groupBy('transactions.id')
        ->get();

       
       
            // Return the combined results to the Blade view
             
        $total_sales = Transaction::leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'sell')            
            ->whereIn('transactions.status', ['final', 'order'])
            ->whereDate('transactions.transaction_date', $date_today)
            ->selectRaw("ROUND(COALESCE(SUM(transaction_payments.amount), 0), ?) as total_sales", [$currency_precision])
            ->value('total_sales');

        // Fetch total service sales
        $total_service = Transaction::leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->whereIn('transactions.type', ['sell', 'autoservice'])            
            ->whereIn('transactions.status', ['final', 'order'])
            ->whereDate('transactions.transaction_date', $date_today)
            ->selectRaw("ROUND(COALESCE(SUM(transaction_payments.amount), 0), ?) as total_services", [$currency_precision])
            ->value('total_services');

        // Fetch total expenses
        $total_expense = Transaction::leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
            ->where('transactions.business_id', $business_id)
            ->where('transactions.type', 'expense') // No need for `whereIn()` with a single value
            ->whereIn('transactions.status', ['final', 'order'])
            ->whereDate('transactions.transaction_date', $date_today)
            ->selectRaw("ROUND(COALESCE(SUM(transaction_payments.amount), 0), ?) as expenses", [$currency_precision])
            ->value('expenses');
         
        return response()->json([
            'success'        => true,
            'total_sales'    => $total_sales,
            'total_services' => $total_service,
            'total_expense'  => $total_expense,
            'totals'        => $totals->toArray()
        ]);

    } catch (\Exception $e) {
        Log::error("Error in daily sales report. File: " . $e->getFile() . " Line: " . $e->getLine() . " Message: " . $e->getMessage());

        return response()->json([
            'success' => false,
            'error'   => 'Something went wrong while fetching data.'
        ], 500);
    }
}


public function selesDetailreport()
{
    $business_id = request()->session()->get('user.business_id');
    $business_details = Business::find($business_id);
    $currency_precision = $business_details->currency_precision ?? config('constants.currency_precision', 2);
    $date_today = now()->toDateString();

    try {
        // Get total sales and payment method breakdown
        $totals = Transaction::leftJoin('transaction_payments', 'transactions.id', '=', 'transaction_payments.transaction_id')
    ->where('transactions.business_id', $business_id)
    ->whereIn('transactions.type', ['sell', 'autoservice']) 
    ->where('transactions.sub_type', 'repair')
    ->whereIn('transactions.status', ['final', 'order'])
    ->whereDate('transactions.transaction_date', $date_today)
    ->selectRaw("
        transactions.invoice_no,
        ROUND(COALESCE(SUM(CASE WHEN transaction_payments.method = 'cash' THEN transaction_payments.amount ELSE 0 END), 0), ?) as total_cash,
        ROUND(COALESCE(SUM(CASE WHEN transaction_payments.method = 'credit' THEN transaction_payments.amount ELSE 0 END), 0), ?) as total_credit,
        ROUND(COALESCE(SUM(CASE WHEN transaction_payments.method = 'card' THEN transaction_payments.amount ELSE 0 END), 0), ?) as total_card,
        ROUND(COALESCE(SUM(transaction_payments.amount), 0), ?) as total_sales
    ", array_fill(0, 4, $currency_precision))
    ->groupBy('transactions.id', 'transactions.invoice_no')
    ->get();

 
        // Get expenses grouped by category
        $expenses = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->leftJoin('transaction_payments AS TP', function ($join) {
                $join->on('transactions.id', '=', 'TP.transaction_id')->where('TP.amount', '!=', 0);
            })
            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->whereIn('transactions.type', ['expense', 'ro_advance', 'ro_salary'])
                      ->orWhere('sub_type', 'expense');
            })
            ->whereDate('transactions.transaction_date', $date_today)
            ->select(
                'ec.name as category',
                DB::raw("ROUND(SUM(TP.amount), {$currency_precision}) as total_expense")
            )
            ->groupBy('transactions.expense_category_id', 'ec.name') // Also group by ec.name to avoid SQL strict errors
            ->get();
 
        return response()->json([
            'success'       => true,
            'totals'        => $totals->toArray(),
          
            'expenses'      => $expenses->toArray()
        ]);
    } catch (\Exception $e) {
        Log::error('Error in daily sales report: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => 'Something went wrong while fetching data.'
        ], 500);
    }
}

public function print(Request $request)
{
    $type = $request->type ?? '';
    $business_id = request()->session()->get('user.business_id');
    $business_locations = BusinessLocation::where('business_id', $business_id)->pluck('name')->first();
    $business_details = Business::find($business_id);
    $subscription = Subscription::active_subscription($business_id);
    $whatsapp_phone_no = 0;
    $date_today = now()->toDateString();
    
    if (!empty($subscription)) {
        $pacakge_details = $subscription->package_details;
        $whatsapp_phone_no = $pacakge_details['whatsapp_phone_no'];
    } 
    
    // Get currency precision from business settings or default to 2
    $currency_precision = !empty($business_details) && !empty($business_details->currency_precision) 
        ? $business_details->currency_precision 
        : config('constants.currency_precision', 2);

    if (request()->ajax() || $type== "pdf") {
        try {
            // Base query - Get transactions with repair job sheets
            $querys = Transaction::leftJoin('repair_job_sheets', 'transactions.repair_job_sheet_id', '=', 'repair_job_sheets.id')
                ->leftJoin('contacts', 'repair_job_sheets.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations', 'transactions.location_id', '=', 'business_locations.id')
                ->leftJoin('repair_device_models', 'repair_job_sheets.vehicle_id', '=', 'repair_device_models.id')
                ->whereNotNull('transactions.repair_job_sheet_id')
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->whereIn('transactions.status', ['final', 'order'])
                ->select([
                    'transactions.id as transaction_id',
                    'transactions.invoice_no as bill_no',
                    'contacts.name as customer',
                    'repair_device_models.name as vehicle_name',
                    'repair_job_sheets.service_type as service_type',
                    'repair_job_sheets.job_sheet_no as job_sheet_no',
                    'repair_job_sheets.service_staff as service_staff',
                    'transactions.final_total as transaction_final_total',
                    'transactions.is_credit_sale'
                ])
                ->orderBy('transactions.id', 'desc');

            // Apply date filter if provided, otherwise use today's date
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $querys->whereDate('transactions.transaction_date', '>=', request()->start_date)
                       ->whereDate('transactions.transaction_date', '<=', request()->end_date);
            } else {
                // Default to today if no date range provided
                $querys->whereDate('transactions.transaction_date', '=', now()->toDateString());
            }

            // Fetch data
            $querys = $querys->get();
            $date = \Carbon::now();
            
            // Debug: Log the raw query results
            Log::info('Service Report Query Results:', [
                'count' => $querys->count(),
                'sample_data' => $querys->take(3)->toArray()
            ]);
            
            // Process data to calculate amounts
            $processedData = [];
            $totalBillAmount = 0;
            $totalCash = 0;
            $totalCard = 0;
            $totalCredit = 0;
            
            foreach ($querys as $query) {
                // Get payment information for this transaction
                $payments = DB::table('transaction_payments')
                    ->where('transaction_id', $query->transaction_id)
                    ->get();
                
                $cash = 0;
                $card = 0;
                $note = '';
                
                // Debug: Log payment info for first few transactions
                if ($query->transaction_id <= 3) {
                    Log::info('Payment info for transaction ' . $query->transaction_id, [
                        'payments_count' => $payments->count(),
                        'payments_data' => $payments->toArray()
                    ]);
                }
                
                foreach ($payments as $payment) {
                    if ($payment->method == 'cash') {
                        $cash += $payment->amount;
                    } elseif ($payment->method == 'card') {
                        $card += $payment->amount;
                    }
                    if (!empty($payment->note)) {
                        $note = $payment->note;
                    }
                }
                
                $credit = $query->is_credit_sale ? round($query->transaction_final_total, $currency_precision) : 0;
                $finalTotal = round($query->transaction_final_total, $currency_precision);
                
                $totalBillAmount += $finalTotal;
                $totalCash += $cash;
                $totalCard += $card;
                $totalCredit += $credit;
                
                $processedData[] = [
                    'job_sheet_no' => $query->job_sheet_no,
                    'bill_no' => $query->bill_no,
                    'customer' => $query->customer,
                    'vehicle_name' => $query->vehicle_name,
                    'service_type' => $query->service_type,
                    'service_staff' => $query->service_staff,
                    'note' => $note,
                    'method' => $cash > 0 ? 'cash' : ($card > 0 ? 'card' : ''),
                    'final_total' => $finalTotal,
                    'cash' => round($cash, $currency_precision),
                    'card' => round($card, $currency_precision),
                    'credit' => $credit
                ];
            }
            
             // Get expenses grouped by category
       
            
           
            
             $expenses = Transaction::leftJoin('expense_categories AS ec', 'transactions.expense_category_id', '=', 'ec.id')
            ->leftJoin('transaction_payments AS TP', function ($join) {
                $join->on('transactions.id', '=', 'TP.transaction_id')->where('TP.amount', '!=', 0);
            })
            ->where('transactions.business_id', $business_id)
            ->where(function ($query) {
                $query->whereIn('transactions.type', ['expense', 'ro_advance', 'ro_salary'])
                      ->orWhere('sub_type', 'expense');
            })
            ->whereDate('transactions.transaction_date', $date_today)
            ->select(
                'ec.name as category',
                DB::raw("ROUND(SUM(TP.amount), {$currency_precision}) as total_expense")
            )
            ->groupBy('transactions.expense_category_id', 'ec.name') // Also group by ec.name to avoid SQL strict errors
            ->get()->toArray();
            
            $expenseHtml='';
             $totalExpenses = 0;
                        foreach ($expenses as $expense) {
                         $amount =$expense['total_expense'] ?? 0;
                        $expenseHtml .= "
                            <tr>
                                <td>".@$expense['category'] ."</td>
                                <td>".round(@$expense['total_expense'], $currency_precision)."</td>
                            </tr";
                        $totalExpenses += $amount;
                    };
                    
                     // Calculate payment summary totals
            $paymentSummary = [
                'total_bill_amount' => round($totalBillAmount, $currency_precision),
                'total_expenses' => round($totalExpenses, $currency_precision),
                'total_cash' => round($totalCash, $currency_precision),
                'total_card' => round($totalCard, $currency_precision),
                'total_credit' => round($totalCredit, $currency_precision),
                'grand_total' => round($totalBillAmount, $currency_precision)
            ];
            
            // Debug: Log the payment summary
            Log::info('Payment Summary Calculated:', $paymentSummary);

            if ($type == "pdf") {
               // Generate PDF using the view
                 $html = view('autorepairservices::servicereport.partials.print-pdf', [
                    'processedData' => $processedData,
                    'business_locations' => $business_locations,
                    'whatsapp_phone_no' => $whatsapp_phone_no,
                    'date' => $date,
                    'paymentSummary' => $paymentSummary,
                     '$expenses' => $expenses
                ])->render();
                return app(GlobalPdfService::class)->stream(
                    $html,
                    'service-report.pdf',
                    ['format' => 'A4', 'orientation' => 'P'],
                    [
                        'business_id' => $business_id,
                        'business_location' => $business_locations,
                        'page_title' => 'Auto Repair Service Report',
                        'date_range' => $date,
                    ]
                );

               // If you want force download:
               // return $pdf->download('service-report.pdf');
            }

            // Return data for DataTable
            return view('autorepairservices::servicereport.partials.print')
                ->with(compact('processedData', 'business_locations', 'whatsapp_phone_no', 'date', 'paymentSummary',  'expenses', 'expenseHtml'));

        } catch (\Exception $e) {
            // Log the error with the exception message
            Log::error('Error occurred in service report index: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            // Return a response indicating an error
            return response()->json([
                'error' => 'Something went wrong while fetching data.'
            ], 500);
        }
    }
}

   
}