<?php



namespace Modules\Essentials\Http\Controllers;



use App\AccountTransaction;

use App\AccountGroup;
use App\Account;
use App\AccountType;

use App\Business;
use App\BusinessLocation;

use App\Category;

use App\Events\TransactionPaymentAdded;

use App\Transaction;

use App\TransactionPayment;

use App\User;

use App\Utils\BusinessUtil;

use App\Utils\ModuleUtil;

use App\Utils\TransactionUtil;

use App\Utils\Util;

use DB;

use Illuminate\Http\Request;

use Illuminate\Http\Response;

use Illuminate\Routing\Controller;

use Illuminate\Support\Facades\View;

use Modules\Essentials\Entities\EssentialsAllowanceAndDeduction;

use Modules\Essentials\Entities\EssentialsLeave;
use Modules\Essentials\Entities\EssentialsEmployee;
use Modules\Essentials\Entities\EssentialsEmployeeAdvance;
use Modules\Essentials\Entities\EssentialsEmployeePaymentSetting;
use Modules\Essentials\Entities\EssentialsUserSalesTarget;

use Modules\Essentials\Entities\PayrollGroup;

use Modules\Essentials\Notifications\PayrollNotification;

use Modules\Essentials\Utils\EssentialsUtil;

use Yajra\DataTables\Facades\DataTables;

use Illuminate\Support\Facades\Auth;


class AdvancesController extends Controller

{

    /**

     * All Utils instance.

     */

    protected $moduleUtil;



    protected $essentialsUtil;



    protected $commonUtil;



    protected $transactionUtil;



    protected $businessUtil;



    /**

     * Constructor

     *

     * @param  ProductUtils  $product

     * @return void

     */

    public function __construct(ModuleUtil $moduleUtil, EssentialsUtil $essentialsUtil, Util $commonUtil, TransactionUtil $transactionUtil, BusinessUtil $businessUtil)

    {

        $this->moduleUtil = $moduleUtil;

        $this->essentialsUtil = $essentialsUtil;

        $this->commonUtil = $commonUtil;

        $this->transactionUtil = $transactionUtil;

        $this->businessUtil = $businessUtil;

    }



    /**

     * Display a listing of the resource.

     *

     * @return Response

     */

    public function index()
    {
        
		$business_id = request()->session()->get('user.business_id');
        $business = Business::where('id', $business_id)->select('id', 'name', 'currency_precision')->first();
		$location = BusinessLocation::where('business_id', $business_id)->first();
		
        if ($location) {
            $location_id = $location->id;
        }
        // dd($business_id);
		$latest_advances = \DB::table('essentials_employee_advances as ea1')
            ->select('ea1.*')
            ->whereRaw('ea1.id = (
                SELECT MAX(ea2.id) 
                FROM essentials_employee_advances ea2 
                WHERE ea2.employee_id = ea1.employee_id
            )');
        
        $allEmployees = \DB::table('essentials_employees')->get();
		
        $employees = \DB::table('essentials_employees')
            ->leftJoinSub($latest_advances, 'latest_advance', function($join) {
                $join->on('latest_advance.employee_id', '=', 'essentials_employees.id');
            })
            ->where('essentials_employees.business_id', $business_id)
            ->select(
                'essentials_employees.id',
                'essentials_employees.name',
                'essentials_employees.employee_no',
                'latest_advance.e_payment_no',
                'latest_advance.datetime_entered',
                'latest_advance.amount'
            )
            // ->whereNot('latest_advance.amount' , 0)
            ->get();

			// Get Expense Accounts
			$expenseAccounts = Account::join('account_types','accounts.account_type_id','=','account_types.id')
				->where('account_types.name','Like','%Expense%')
				->where(['account_types.business_id'=>$business_id])
				->where(['accounts.business_id'=>$business_id])
				->select(
					'accounts.id',
					'accounts.name',
					'accounts.visible',
					'accounts.account_type_id',
					'account_types.default_account_type_id',
					'accounts.account_number',
					'account_types.name as asset_name',
					'account_types.id as asset_id',
					'account_types.business_id'
				)->get();

			// Get Current Liability Accounts
			$liabilityAccounts = Account::join('account_types','accounts.account_type_id','=','account_types.id')
				->where('account_types.name','Current Liabilities')
				->where(['account_types.business_id'=>$business_id])
				->where(['accounts.business_id'=>$business_id])
				->select(
					'accounts.id',
					'accounts.name',
					'accounts.visible',
					'accounts.account_type_id',
					'account_types.default_account_type_id',
					'accounts.account_number',
					'account_types.name as asset_name',
					'account_types.id as asset_id',
					'account_types.business_id'
				)->get();

			// Keep the old paymentMethod for backward compatibility (combining both)
			$paymentMethod = $expenseAccounts->merge($liabilityAccounts);
			
			// return count($paymentMethod);

			
			$paymentType = EssentialsEmployeePaymentSetting::leftJoin('accounts as liability_account','essentials_employee_payment_settings.liability_account_id','=','liability_account.id')
			->leftJoin('accounts as expense_account','essentials_employee_payment_settings.expense_account_id','=','expense_account.id')
			->where('essentials_employee_payment_settings.status','=',1 )
			->where('essentials_employee_payment_settings.status','=',1 )
			->where('essentials_employee_payment_settings.business_id','=',$business_id)
				->select(
					'essentials_employee_payment_settings.id',
					'essentials_employee_payment_settings.name',
					'essentials_employee_payment_settings.liability_account_id',
					'essentials_employee_payment_settings.expense_account_id',
					'essentials_employee_payment_settings.remarks',
					'essentials_employee_payment_settings.status',
					'essentials_employee_payment_settings.need_amount',
					'essentials_employee_payment_settings.datetime_entered',
					'essentials_employee_payment_settings.employee_ledger',
					'liability_account.name as liable_bank',
					'liability_account.account_number as liable_account_no',
					'expense_account.name as expense_account_name',
					'expense_account.account_number as expense_account_no')
				->get();
				
			$users = User::where(['business_id'=>$business_id])->first();
			
			
			
		$advances = EssentialsEmployeeAdvance::join('essentials_employees', 'essentials_employee_advances.employee_id', '=', 'essentials_employees.id')
			->leftJoin('essentials_employee_payment_settings', 'essentials_employee_advances.payment_type_id', '=', 'essentials_employee_payment_settings.id')
			->leftJoin('accounts as payment_method_account', 'essentials_employee_advances.account_id', '=', 'payment_method_account.id')
			->where('essentials_employees.business_id', $business_id)
			->select(
				'essentials_employee_advances.id',
				'essentials_employees.name',
				'essentials_employees.employee_no',
				'essentials_employee_advances.e_payment_no',
				'essentials_employee_advances.amount',
				'essentials_employee_advances.datetime_entered',
				'essentials_employee_advances.payment_type_id',
				'essentials_employee_advances.salary_period_start',
				'essentials_employee_advances.salary_period_end',
				'essentials_employee_advances.amount_paid',
				'essentials_employee_advances.payment_status',
				'essentials_employee_advances.account_id',
				'essentials_employee_advances.created_at',
				'essentials_employee_advances.check_no',
				'essentials_employee_payment_settings.name as payment_type_name',
				'payment_method_account.name as payment_method_name'
			)
			->orderBy('essentials_employee_advances.created_at', 'DESC')
			->get();
			
		$settings = EssentialsEmployeePaymentSetting::with('user')->where(['status'=>1, 'business_id'=>$business_id])->get();
			
		// 		return json_encode($users);
		
		foreach($employees as $employee){
			$employee->amount = 0;
			$employee->amount_paid = 0;
		}
		$today = date('m/d/Y');
        $startdate = date('Y-m-01');
        $enddate = date('Y-m-t');
		
		$assettypes = [28,33,34]; // assets
		// 		$liabilitytypes = [29,35,36]; // liabilities
        $liabilitytypes = [8]; // "Current Liability"

		$rawaccounts = Account::whereIn('account_type_id',array_merge($assettypes, $liabilitytypes))->where('business_id',1)->get();
        
        // $rawaccounts = Account::whereIn('account_type_id', $liabilitytypes)
        //     ->where('business_id', 1) // Ensuring business-specific accounts
        //     ->get();
            
        // dd($rawaccounts); 
		$accounts = [
			0 => [], // Liabilities
			1 => []  // Assets
		];
		
		foreach($rawaccounts as $account){
			if(in_array($account->account_type_id,$liabilitytypes)){
				$accounts[0][] = $account->toArray();
			}else{
				$accounts[1][] = $account->toArray();
			}
		}
		
		
		$accounts_with_check = [];
		foreach($accounts[1] as $account){
			
			if (strpos($account['name'], 'Bank') !== false || strpos($account['name'], 'Cheque') !== false || strpos($account['name'], 'Check') !== false) {
				$accounts_with_check[] = $account['id'];
			}

		}
		$lastedidd = EssentialsEmployeeAdvance::orderBy('id', 'desc')->first('id');
		if($lastedidd){
			$lastedidd = $lastedidd->id + 1;
		}else{
			$lastedidd = 1;
		}
		
		
        return view('essentials::advances.index')->with(compact(
            'location_id',
            'employees',
            'advances',
            'settings',
            'business',
            'today',
            'startdate',
            'enddate',
            'accounts',
            'accounts_with_check',
            'paymentMethod',
            'expenseAccounts',
            'liabilityAccounts',
            'paymentType',
            'users',
			'lastedidd',
            'allEmployees'
        ));

    }
    
    public function getLiabilityAccounts()
    {
        dd("Nothing Here");
    }
	
	public function removePaymentSettings(Request $request){
		
		$result = true;
		$message = "";
		$user = Auth::user();
		
		$business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
		
		$data = $request->all();
		
		$setting = null;
		if($is_admin){
			$id = $request->input('id');
			$setting = EssentialsEmployeePaymentSetting::find($id);
			if($setting){
				$setting->status = 0;
			}
			
			if($setting->save()){
				
			}else{
				$result = false;
			}
		}
		return compact('result','message');
	}
	public function savePaymentSettings(Request $request){
		
		$result = true;
		$message = "";
		$user = Auth::user();
		
		$business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
		
		$data = $request->all();
		
		$setting = null;
		if($is_admin){
			if(isset($data['id']) && strlen($data['id'])){
				$setting = EssentialsEmployeePaymentSetting::find($data['id']);
			}else{
				$setting = new EssentialsEmployeePaymentSetting();
			}
			$existData = EssentialsEmployeePaymentSetting::where('name',$data['payment_type'])->where('business_id',$business_id)->get();
			if($existData):
				$result = false;
				$message = "Payment type already exist";
			endif;
			$setting->liability_account_id = $data['liability_account_id'];
			$setting->expense_account_id = $data['expense_account_id'];
			$setting->name = $data['payment_type'];
			$setting->employee_ledger = $data['employee_ledger'];
			$setting->need_amount = $data['need_amount'];
			$setting->business_id = $business_id;
			if(isset($data['remarks'])){
				$setting->remarks = $data['remarks'];
			}
			if(isset($data['date'])){
				$setting->datetime_entered = date('Y-m-d H:i:s',strtotime($data['date']));
			}else{
				$setting->datetime_entered = date('Y-m-d H:i:s');
			}
			$setting->user_id = $user->id;
			if($setting->save()){
				$result = true;
				$message = "Payment type save successfully";
			}else{
				$result = false;
				$message = "Unable to save setting. Please try again.";
			}
		}

		
		
		$setting = EssentialsEmployeePaymentSetting::with('user')
			->leftJoin('accounts as liability_account', 'essentials_employee_payment_settings.liability_account_id', '=', 'liability_account.id')
			->leftJoin('accounts as expense_account', 'essentials_employee_payment_settings.expense_account_id', '=', 'expense_account.id')
			->where('essentials_employee_payment_settings.id', $setting->id)
			->select(
				'essentials_employee_payment_settings.*',
				'liability_account.name as liable_bank',
				'expense_account.name as expense_account_name'
			)
			->first();
		
		return compact('setting','result','message');
	}
	
	public function saveAdvance(Request $request){
		$result = true;
		$message = "";
		
		$business_id = $request->session()->get('user.business_id');
        $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
		
		$data = $request->all();
		
		if(isset($data['id']) && strlen($data['id'])){
			
			$advance = EssentialsEmployeeAdvance::find($data['id']);
			
			$advance->amount = $data['amount'];
			$advance->amount_paid = $data['amount_paid'];
			$advance->remarks = isset($data['remarks'])?$data['remarks']:null;
			$advance->payment_status = $data['payment_status'];
			if($advance->save()){
				$result = true;
				$message = "Data save successfully";
			}else{
				$result = false;
				$message = "Unable to save Employee Advance.";
			}
		}
		
		return compact('result','message');
	}
	
	// public function saveAdvancePayments(Request $request){
	// 	$result = true;
	// 	$message = "";
	// 	dd($request);
	// 	$business_id = $request->session()->get('user.business_id');
    //     $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
	// 	$data = [];
	// 	if($is_admin){
	// 		$data = $request->all();
	// 						$total = 0;
	// 			if(isset($data['advances'])){
	// 				$creditId = $data['payment']['payment_method_id'];
	// 				$debitAccount  = EssentialsEmployeePaymentSetting::find($data['payment']['payment_type_id']);

	// 				// Get the next E Payment number
	// 				$lastAdvance = EssentialsEmployeeAdvance::orderBy('id', 'desc')->first();
	// 				$nextNumber = $lastAdvance ? (intval(substr($lastAdvance->e_payment_no, 3)) + 1) : 1;

	// 				foreach($data['advances'] as $advance){
	// 				// if($advance['amount'] > 0){
					
	// 										$input = [
	// 					'employee_id'     => $advance['id'],
	// 					'e_payment_no'    => 'EP-'.str_pad($nextNumber, 4, '0', STR_PAD_LEFT),
	// 					'amount'          => $advance['amount'],
	// 					'amount_paid'     => $advance['amount_paid'],
	// 					'payment_type_id' => isset($data['payment']['payment_type_id'])?$data['payment']['payment_type_id']:null,
	// 					'payment_status'  => EssentialsEmployeeAdvance::PAYMENT_STATUS_NEW,
	// 				];
	// 				$nextNumber++;

	// 					$total += $advance['amount'];
	// 					if(isset($data['payment']['payment_method_id'])){
	// 						$input['account_id'] = $data['payment']['payment_method_id'];
	// 					}
	// 					if(isset($data['payment']['check'])){
	// 						$input['check_no'] = $data['payment']['check'];
	// 					}
	// 					if(isset($data['payment']['account'])){
	// 						// todo: need to confirm what should happen here. perform debit/credit on accounts
	// 						//$input['account'] = $data['payment']['account'];
							
	// 					}
	// 					if(isset($data['payment']['date'])){
	// 						$input['datetime_entered'] = date('Y-m-d',strtotime($data['payment']['date']));
	// 					}
	// 					if(isset($data['payment']['salary_period'])){
	// 						$periods = explode(" to ",$data['payment']['salary_period']);
	// 						if(count($periods) == 2){
	// 							$input['salary_period_start'] = $periods[0];
	// 							$input['salary_period_end'] = $periods[1];
	// 						}
	// 					}
						
	// 					$advancePayment = EssentialsEmployeeAdvance::create($input);
	// 				$advanceId = $advancePayment->id;
					
	// 				// Get the E Payment number for this advance
	// 				$ePaymentNo = $input['e_payment_no'];
					
	// 				// Get payment settings for expense and liability accounts
	// 				$paymentSetting = EssentialsEmployeePaymentSetting::find($data['payment']['payment_type_id']);
	// 				$paymentMethodAccount = Account::find($creditId);
	// 				$expenseAccount = null;
	// 				$liabilityAccount = null;
					
	// 				if ($paymentSetting) {
	// 					$expenseAccount = Account::find($paymentSetting->expense_account_id);
	// 					$liabilityAccount = Account::find($paymentSetting->liability_account_id);
	// 				}
					
	// 				// Get payment type name
	// 				$paymentTypeName = $paymentSetting ? $paymentSetting->name : 'Unknown';
					
	// 				// Get salary period
	// 				$salaryPeriod = '';
	// 				if (isset($data['payment']['salary_period'])) {
	// 					$salaryPeriod = $data['payment']['salary_period'];
	// 				}
					
	// 				// Get cheque number
	// 				$chequeNo = '';
	// 				if (isset($data['payment']['check'])) {
	// 					$chequeNo = $data['payment']['check'];
	// 				}
					
	// 				// Get payment method name
	// 				$paymentMethodName = $paymentMethodAccount ? $paymentMethodAccount->name : 'Unknown';
					
	// 				// Transaction date
	// 				$transactionDate = date('Y-m-d H:i:s');
					
	// 				// i. Total Amount in "Amount" column to Expense Account (Debit)
	// 				if ($expenseAccount) {
	// 					$accountTransaction = new AccountTransaction();
	// 					$accountTransaction->account_id = $expenseAccount->id;
	// 					$accountTransaction->amount = $advance['amount'];
	// 					$accountTransaction->type = 'debit';
	// 					$accountTransaction->txnType = 'advance';
	// 					$accountTransaction->employee_advance_id = $advanceId;
	// 					$accountTransaction->journal_deleted = 0;
	// 					$accountTransaction->reconcile_status = 0;
	// 					$accountTransaction->postdated_transafer_status = 0;
	// 					$accountTransaction->operation_date = $transactionDate;
	// 					$accountTransaction->created_by = Auth::user()->id;
	// 					$accountTransaction->business_id = $business_id;
	// 					$accountTransaction->cheque_number = $chequeNo;
	// 					$accountTransaction->note = "E Payment No: " . $ePaymentNo . 
	// 						", Salary Period: " . $salaryPeriod . 
	// 						", Liability Account: " . ($liabilityAccount ? $liabilityAccount->name : 'N/A') . 
	// 						", Payment Method: " . $paymentMethodName . 
	// 						", Payment Type: " . $paymentTypeName;
	// 					if(!$accountTransaction->save()){
	// 						$result = false;
	// 						$message = "Unable to save expense account transaction.";
	// 					}
	// 				}
					
	// 				// ii. Total Amount in "Amount" column to Liability Account (Credit)
	// 				if ($liabilityAccount) {
	// 					$accountTransaction = new AccountTransaction();
	// 					$accountTransaction->account_id = $liabilityAccount->id;
	// 					$accountTransaction->amount = $advance['amount'];
	// 					$accountTransaction->type = 'credit';
	// 					$accountTransaction->txnType = 'advance';
	// 					$accountTransaction->employee_advance_id = $advanceId;
	// 					$accountTransaction->journal_deleted = 0;
	// 					$accountTransaction->reconcile_status = 0;
	// 					$accountTransaction->postdated_transafer_status = 0;
	// 					$accountTransaction->operation_date = $transactionDate;
	// 					$accountTransaction->created_by = Auth::user()->id;
	// 					$accountTransaction->business_id = $business_id;
	// 					$accountTransaction->cheque_number = $chequeNo;
	// 					$accountTransaction->note = "E Payment No: " . $ePaymentNo . 
	// 						", Salary Period: " . $salaryPeriod . 
	// 						", Expense Account: " . ($expenseAccount ? $expenseAccount->name : 'N/A') . 
	// 						", Payment Method: " . $paymentMethodName . 
	// 						", Payment Type: " . $paymentTypeName;
	// 					if(!$accountTransaction->save()){
	// 						$result = false;
	// 						$message = "Unable to save liability account transaction.";
	// 					}
	// 				}
					
	// 				// iii. Total Amount in "Amount Paid" column to Payment Method Account (Credit)
	// 				if ($paymentMethodAccount) {
	// 					$accountTransaction = new AccountTransaction();
	// 					$accountTransaction->account_id = $paymentMethodAccount->id;
	// 					$accountTransaction->amount = $advance['amount_paid'];
	// 					$accountTransaction->type = 'credit';
	// 					$accountTransaction->txnType = 'advance';
	// 					$accountTransaction->employee_advance_id = $advanceId;
	// 					$accountTransaction->journal_deleted = 0;
	// 					$accountTransaction->reconcile_status = 0;
	// 					$accountTransaction->postdated_transafer_status = 0;
	// 					$accountTransaction->operation_date = $transactionDate;
	// 					$accountTransaction->created_by = Auth::user()->id;
	// 					$accountTransaction->business_id = $business_id;
	// 					$accountTransaction->cheque_number = $chequeNo;
	// 					$accountTransaction->note = "E Payment No: " . $ePaymentNo . 
	// 						", Salary Period: " . $salaryPeriod . 
	// 						", Liability Account: " . ($liabilityAccount ? $liabilityAccount->name : 'N/A') . 
	// 						", Expense Account: " . ($expenseAccount ? $expenseAccount->name : 'N/A') . 
	// 						", Payment Type: " . $paymentTypeName;
	// 					if(!$accountTransaction->save()){
	// 						$result = false;
	// 						$message = "Unable to save payment method account transaction.";
	// 					}
	// 				}
					
	// 				// iv. Total Amount in "Amount Paid" column to Liability Account (Debit)
	// 				if ($liabilityAccount) {
	// 					$accountTransaction = new AccountTransaction();
	// 					$accountTransaction->account_id = $liabilityAccount->id;
	// 					$accountTransaction->amount = $advance['amount_paid'];
	// 					$accountTransaction->type = 'debit';
	// 					$accountTransaction->txnType = 'advance';
	// 					$accountTransaction->employee_advance_id = $advanceId;
	// 					$accountTransaction->journal_deleted = 0;
	// 					$accountTransaction->reconcile_status = 0;
	// 					$accountTransaction->postdated_transafer_status = 0;
	// 					$accountTransaction->operation_date = $transactionDate;
	// 					$accountTransaction->created_by = Auth::user()->id;
	// 					$accountTransaction->business_id = $business_id;
	// 					$accountTransaction->cheque_number = $chequeNo;
	// 					$accountTransaction->note = "E Payment No: " . $ePaymentNo . 
	// 						", Salary Period: " . $salaryPeriod . 
	// 						", Liability Account: " . $liabilityAccount->name . 
	// 						", Payment Method Account: " . $paymentMethodName . 
	// 						", Payment Type: " . $paymentTypeName;
	// 					if(!$accountTransaction->save()){
	// 						$result = false;
	// 						$message = "Unable to save liability account debit transaction.";
	// 					}
	// 				}
	// 			}
	// 		}
	// 	}

	// 	// Get all created advances with proper data structure for the list page

    //     $employeeIds = collect($data['advances'])->pluck('id')->toArray();

    //     $subQuery = DB::table('essentials_employee_advances as sub')
    //         ->select('sub.employee_id', DB::raw('MAX(sub.created_at) as last_created'))
    //         ->whereIn('sub.employee_id', $employeeIds)
    //         ->groupBy('sub.employee_id');

    //     $createdAdvances = EssentialsEmployeeAdvance::join('essentials_employees', 'essentials_employee_advances.employee_id', '=', 'essentials_employees.id')
    //         ->leftJoin('essentials_employee_payment_settings', 'essentials_employee_advances.payment_type_id', '=', 'essentials_employee_payment_settings.id')
    //         ->leftJoin('accounts as payment_method_account', 'essentials_employee_advances.account_id', '=', 'payment_method_account.id')
    //         ->joinSub($subQuery, 'latest', function ($join) {
    //             $join->on('essentials_employee_advances.employee_id', '=', 'latest.employee_id')
    //                 ->on('essentials_employee_advances.created_at', '=', 'latest.last_created');
    //         })
    //         ->where('essentials_employees.business_id', $business_id)
    //         ->select(
    //             'essentials_employee_advances.id',
    //             'essentials_employees.name',
    //             'essentials_employees.employee_no',
    //             'essentials_employee_advances.e_payment_no',
    //             'essentials_employee_advances.amount',
    //             'essentials_employee_advances.datetime_entered',
    //             'essentials_employee_advances.payment_type_id',
    //             'essentials_employee_advances.salary_period_start',
    //             'essentials_employee_advances.salary_period_end',
    //             'essentials_employee_advances.amount_paid',
    //             'essentials_employee_advances.payment_status',
    //             'essentials_employee_advances.account_id',
    //             'essentials_employee_advances.created_at',
    //             'essentials_employee_advances.check_no',
    //             'essentials_employee_payment_settings.name as payment_type_name',
    //             'payment_method_account.name as payment_method_name'
    //         )
    //         ->orderBy('essentials_employee_advances.created_at', 'DESC')
    //         ->get();

    //     return compact('result','message','is_admin','business_id','data','createdAdvances');
	// }


	public function saveAdvancePayments(Request $request)
{
    $result = true;
    $message = "";

    $business_id = $request->session()->get('user.business_id');
    $is_admin = $this->moduleUtil->is_admin(auth()->user(), $business_id);
    $data = $request->all();

    // Validate that advances exist
    if (empty($data['advances']) || count($data['advances']) === 0) {
        return response()->json([
            'result' => false,
            'message' => 'No advances data received.',
            'is_admin' => $is_admin,
            'business_id' => $business_id,
            'data' => $data,
            'createdAdvances' => []
        ]);
    }

    DB::beginTransaction();
    try {
        $totalAmount = 0;
        $totalAmountPaid = 0;

        $paymentMethodId = $data['payment']['payment_method_id'];
        $paymentSetting = EssentialsEmployeePaymentSetting::find($data['payment']['payment_type_id']);
        
        if (!$paymentSetting) {
            throw new \Exception("Payment type setting not found.");
        }

        // Get accounts
        $paymentMethodAccount = Account::find($paymentMethodId);
        $expenseAccount = Account::find($paymentSetting->expense_account_id);
        $liabilityAccount = Account::find($paymentSetting->liability_account_id);

        if (!$expenseAccount || !$liabilityAccount || !$paymentMethodAccount) {
            throw new \Exception("Required accounts not found. Please check account configuration.");
        }

        // Get the next E Payment number
        $lastAdvance = EssentialsEmployeeAdvance::orderBy('id', 'desc')->first();
        $nextNumber = $lastAdvance ? (intval(substr($lastAdvance->e_payment_no, 3)) + 1) : 1;
        $ePaymentNo = 'EP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        // Common variables
        $chequeNo = $data['payment']['check'] ?? '';
        $salaryPeriod = $data['payment']['salary_period'] ?? '';
        $description = $data['payment']['description'] ?? '';
        $transactionDate = date('Y-m-d H:i:s');

        foreach ($data['advances'] as $advance) {
            $input = [
                'employee_id'     => $advance['id'],
                'e_payment_no'    => $ePaymentNo,
                'amount'          => $advance['amount'],
                'amount_paid'     => $advance['amount_paid'],
                'payment_type_id' => $data['payment']['payment_type_id'] ?? null,
                'payment_status'  => EssentialsEmployeeAdvance::PAYMENT_STATUS_NEW,
            ];

            $totalAmount += $advance['amount'];
            $totalAmountPaid += $advance['amount_paid'];

            // Optional fields
            if (!empty($paymentMethodId)) {
                $input['account_id'] = $paymentMethodId;
            }
            if (!empty($chequeNo)) {
                $input['check_no'] = $chequeNo;
            }
            if (!empty($data['payment']['date'])) {
                $input['datetime_entered'] = date('Y-m-d', strtotime($data['payment']['date']));
            }
            if (!empty($salaryPeriod)) {
                $periods = explode(" to ", $salaryPeriod);
                if (count($periods) == 2) {
                    $input['salary_period_start'] = $periods[0];
                    $input['salary_period_end'] = $periods[1];
                }
            }

            $advancePayment = EssentialsEmployeeAdvance::create($input);
            $advanceId = $advancePayment->id;

            /**
             * i. Expense Account (Debit) - Total Amount
             * Requirements: Amount column, debit side, with cheque no, description, e payment no, salary period, liability account
             */
            $expenseNote = "E Payment No: $ePaymentNo, Salary Period: $salaryPeriod, Liability Account: {$liabilityAccount->name}";
            $this->createAccountTransaction(
                $expenseAccount->id, 
                $advance['amount'], 
                'debit', 
                $advanceId, 
                $transactionDate,
                $business_id, 
                $chequeNo, 
                $description,
                $expenseNote
            );

            /**
             * ii. Liability Account (Credit) - Total Amount
             * Requirements: Amount column, credit side, with cheque no, description, e payment no, salary period, expense account
             */
            $liabilityCreditNote = "E Payment No: $ePaymentNo, Salary Period: $salaryPeriod, Expense Account: {$expenseAccount->name}";
            $this->createAccountTransaction(
                $liabilityAccount->id, 
                $advance['amount'], 
                'credit', 
                $advanceId, 
                $transactionDate,
                $business_id, 
                $chequeNo, 
                $description,
                $liabilityCreditNote
            );

            /**
             * iii. Payment Method Account (Credit) - Amount Paid
             * Requirements: Amount Paid column, credit side, with cheque no, description, e payment no, salary period, liability account, expense account
             */
            $paymentMethodNote = "E Payment No: $ePaymentNo, Salary Period: $salaryPeriod, " .
                                "Liability Account: {$liabilityAccount->name}, " .
                                "Expense Account: {$expenseAccount->name}";
            $this->createAccountTransaction(
                $paymentMethodAccount->id, 
                $advance['amount_paid'], 
                'credit', 
                $advanceId, 
                $transactionDate,
                $business_id, 
                $chequeNo, 
                $description,
                $paymentMethodNote
            );

            /**
             * iv. Liability Account (Debit) - Amount Paid
             * Requirements: Amount Paid column, debit side, with cheque no, description, e payment no, salary period, liability account
             */
            $liabilityDebitNote = "E Payment No: $ePaymentNo, Salary Period: $salaryPeriod, Liability Account: {$liabilityAccount->name}";
            $this->createAccountTransaction(
                $liabilityAccount->id, 
                $advance['amount_paid'], 
                'debit', 
                $advanceId, 
                $transactionDate,
                $business_id, 
                $chequeNo, 
                $description,
                $liabilityDebitNote
            );

            $nextNumber++;
            $ePaymentNo = 'EP-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        }

        DB::commit();
        $message = "Advance payments saved successfully.";

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Advance Payment Error: ' . $e->getMessage());
        return response()->json([
            'result' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'is_admin' => $is_admin,
            'business_id' => $business_id,
            'data' => $data,
            'createdAdvances' => []
        ]);
    }

    // Rest of your code for returning created advances...
    $employeeIds = collect($data['advances'])->pluck('id')->toArray();

    $subQuery = DB::table('essentials_employee_advances as sub')
        ->select('sub.employee_id', DB::raw('MAX(sub.created_at) as last_created'))
        ->whereIn('sub.employee_id', $employeeIds)
        ->groupBy('sub.employee_id');

    $createdAdvances = EssentialsEmployeeAdvance::join('essentials_employees', 'essentials_employee_advances.employee_id', '=', 'essentials_employees.id')
        ->leftJoin('essentials_employee_payment_settings', 'essentials_employee_advances.payment_type_id', '=', 'essentials_employee_payment_settings.id')
        ->leftJoin('accounts as payment_method_account', 'essentials_employee_advances.account_id', '=', 'payment_method_account.id')
        ->joinSub($subQuery, 'latest', function ($join) {
            $join->on('essentials_employee_advances.employee_id', '=', 'latest.employee_id')
                ->on('essentials_employee_advances.created_at', '=', 'latest.last_created');
        })
        ->where('essentials_employees.business_id', $business_id)
        ->select(
            'essentials_employee_advances.id',
            'essentials_employees.name',
            'essentials_employees.employee_no',
            'essentials_employee_advances.e_payment_no',
            'essentials_employee_advances.amount',
            'essentials_employee_advances.datetime_entered',
            'essentials_employee_advances.payment_type_id',
            'essentials_employee_advances.salary_period_start',
            'essentials_employee_advances.salary_period_end',
            'essentials_employee_advances.amount_paid',
            'essentials_employee_advances.payment_status',
            'essentials_employee_advances.account_id',
            'essentials_employee_advances.created_at',
            'essentials_employee_advances.check_no',
            'essentials_employee_payment_settings.name as payment_type_name',
            'payment_method_account.name as payment_method_name'
        )
        ->orderBy('essentials_employee_advances.created_at', 'DESC')
        ->get();

    return compact('result', 'message', 'is_admin', 'business_id', 'data', 'createdAdvances');
}


/**
 * Helper function to create transactions
 */
private function createAccountTransaction($accountId, $amount, $type, $advanceId, $transactionDate, $business_id, $chequeNo, $description, $note)
{
    $accountTransaction = new AccountTransaction();
    $accountTransaction->account_id = $accountId;
    $accountTransaction->amount = $amount;
    $accountTransaction->type = $type;
    $accountTransaction->txnType = 'advance';
    $accountTransaction->employee_advance_id = $advanceId;
    $accountTransaction->journal_deleted = 0;
    $accountTransaction->reconcile_status = 0;
    $accountTransaction->postdated_transafer_status = 0;
    $accountTransaction->operation_date = $transactionDate;
    $accountTransaction->created_by = Auth::user()->id;
    $accountTransaction->business_id = $business_id;
    $accountTransaction->cheque_number = $chequeNo ?: null;
    $accountTransaction->note = trim($description . ' | ' . $note, ' |');

    return $accountTransaction->save();
}

}

