<?php

namespace Modules\MyAuto\Http\Controllers;

use App\Business;
use App\Utils\BusinessUtil;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\MyAuto\Entities\MyAutoDailyLog;
use Modules\MyAuto\Entities\MyAutoSetting;
use Modules\MyAuto\Entities\MyAutoTransaction;
use Modules\Superadmin\Entities\Subscription;
use Modules\Superadmin\Entities\Package;

class MyAutoController extends Controller
{
    public function __construct(protected BusinessUtil $businessUtil) {}

    /* =========================
       MY AUTO HOME PAGE
    ==========================*/
    public function index(Request $request)
    {
        Log::info('here in index');
        $business_id = $request->session()->get('user.business_id');
        $user_id     = auth()->id();

        $setting = MyAutoSetting::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->first();

        if (! $setting) {
            $output = [
                'success' => true,
                'msg'     => __('Please complete My Auto Settings first.'),
            ];
            return redirect()->route('myauto.settings')->with(['status' => $output]);
        }

        $today = Carbon::today()->toDateString();

        $todayLog = MyAutoDailyLog::firstOrCreate(
            [
                'business_id'        => $business_id,
                'my_auto_setting_id' => $setting->id,
                'log_date'           => $today,
            ],
            [
                'starting_meter' => $this->resolveStartingMeter($setting, $business_id),
            ]
        );

        $totalIncome = MyAutoDailyLog::where('business_id', $business_id)
            ->where('my_auto_setting_id', $setting->id)
            ->sum('trip_income');

        $totalExpense = MyAutoDailyLog::where('business_id', $business_id)
            ->where('my_auto_setting_id', $setting->id)
            ->selectRaw('SUM(expense_1 + expense_2 + expense_3 + expense_4 + expense_5) as total')
            ->value('total') ?? 0;

        $incomeTransactions = MyAutoTransaction::where('my_auto_daily_log_id', $todayLog->id)
            ->where('type', 'income')
            ->orderBy('created_at', 'asc')
            ->get();
        $incomeTotal = $incomeTransactions->sum('amount');

        $expenseTransactions = MyAutoTransaction::where('my_auto_daily_log_id', $todayLog->id)
            ->where('type', 'expense')
            ->orderBy('created_at', 'asc')
            ->get();

        $expenseMap = [
            'expense_1' => 'Petrol / Diesel',
            'expense_2' => 'Oil',
            'expense_3' => 'Repairs',
            'expense_4' => 'Meals',
            'expense_5' => 'Others',
        ];
        $active_subscription = Subscription::active_subscription($business_id);
        $waiting_subscription = Subscription::waiting_approval($business_id)->first();
        $subscription_remaining_days = null;
        $my_auto_packages = Package::active()
            ->visible()
            ->where(function ($query) {
                $query->where('auto_services_and_repair_module', 1)
                    ->orWhere('home_dashboard', 1);
            })
            ->orderBy('sort_order')
            ->pluck('name', 'id');

        if (!empty($active_subscription) && !empty($active_subscription->end_date)) {
            $subscription_remaining_days = max(Carbon::today()->diffInDays(Carbon::parse($active_subscription->end_date), false), 0);
        }

        Log::info('returning index view');

        return view('myauto::home', compact(
            'setting',
            'todayLog',
            'totalIncome',
            'totalExpense',
            'incomeTransactions',
            'incomeTotal',
            'expenseTransactions',
            'expenseMap',
            'subscription_remaining_days',
            'waiting_subscription',
            'my_auto_packages'
        ));
    }

    private function resolveStartingMeter($setting, $business_id)
    {
        $lastLog = MyAutoDailyLog::where('business_id', $business_id)
            ->where('my_auto_setting_id', $setting->id)
            ->whereNotNull('current_meter')
            ->orderBy('log_date', 'desc')
            ->first();

        return $lastLog
            ? $lastLog->current_meter
            : $setting->starting_meter;
    }

    /* =========================
       MY AUTO SETTINGS PAGE
    ==========================*/
    public function settings(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $business_id = $request->session()->get('user.business_id');
        Log::info('business id in my auto setting: ' . $business_id);

        $setting = MyAutoSetting::where('business_id', $business_id)
            // ->where('user_id', $user_id)
            ->first();
        Log::info('my auto setting found: ' . $setting);

        $user = auth()->user();

        Log::info($user->hasRole('Admin#' . $user->business_id));

        return view('myauto::settings', compact('setting', 'business_id'));
    }

    public function storeSettings(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id     = auth()->id();

        $existing = MyAutoSetting::where('business_id', $business_id)
            // ->where('user_id', $user_id)
            ->first();

        $user = auth()->user();

        $canEdit = $user->hasRole('Super Admin')
            || $user->hasRole('Admin#' . $business_id);

        if ($existing && ! $canEdit) {
            abort(403, 'You are not allowed to edit these settings.');
        }

        $request->validate([
            'user_name'          => 'required',
            'first_date'         => 'required|date',
            'starting_meter'     => 'required|numeric',
            'auto_number'        => 'required',
            'passcode'           => 'required|string|min:4|max:8|confirmed',
            'sms_mobile_numbers' => 'nullable|string',
        ]);

        $oldPasscode = $existing?->passcode;

        MyAutoSetting::updateOrCreate(
            [
                'business_id' => $business_id,
                'user_id'     => $user_id,
            ],
            [
                'user_name'          => $request->user_name,
                'first_date'         => $request->first_date,
                'starting_meter'     => $request->starting_meter,
                'auto_number'        => $request->auto_number,
                'passcode'           => $request->passcode,
                'sms_mobile_numbers' => $request->sms_mobile_numbers,
                'is_sms_enabled'     => $request->has('is_sms_enabled'),
                'is_locked'          => true,
            ]
        );

        $response = redirect()->route('myauto.index');

        // Send SMS when passcode changes via settings form
        if (
            $existing !== null
            && $request->passcode !== $oldPasscode
            && $request->has('is_sms_enabled')
            && $request->filled('sms_mobile_numbers')
        ) {
            if (function_exists('fastcgi_finish_request')) {
                $response->send();
                fastcgi_finish_request();
            }

            $business    = Business::find($business_id);
            $smsSettings = empty($business->sms_settings)
                ? $this->businessUtil->defaultSmsSettings()
                : $business->sms_settings;

            $this->businessUtil->sendSms([
                'business_id'   => $business_id,
                'mobile_number' => $request->sms_mobile_numbers,
                'sms_body'      => 'My Auto system Passcode Changed. Your New Passcode is ' . $request->passcode,
                'sms_settings'  => $smsSettings,
            ], 'Passcode Change');
        }

        return $response;
    }

    /* =========================
       DAILY OPERATIONS
    ==========================*/
    public function updateMeter(Request $request)
    {
        $log = MyAutoDailyLog::findOrFail($request->log_id);

        if ($request->meter < $log->starting_meter) {
            return response()->json(['error' => 'Invalid meter'], 422);
        }

        $log->update(['current_meter' => $request->meter]);
        return response()->json(['success' => true]);
    }

    public function addTripIncome(Request $request)
    {
        $request->validate([
            'log_id' => 'required|exists:my_auto_daily_logs,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        $log = MyAutoDailyLog::findOrFail($request->log_id);

        // Create transaction
        MyAutoTransaction::create([
            'my_auto_daily_log_id' => $log->id,
            'type' => 'income',
            'amount' => $request->amount,
        ]);

        // Update summary column
        $log->increment('trip_income', $request->amount);

        return response()->json(['success' => true]);
    }

    public function addExpense(Request $request)
    {
        $request->validate([
            'log_id' => 'required|exists:my_auto_daily_logs,id',
            'field'  => 'required|integer|min:1|max:5',
            'amount' => 'required|numeric|min:0.01',
            'note'   => 'nullable|string|max:255',
        ]);

        $log = MyAutoDailyLog::findOrFail($request->log_id);

        $field = 'expense_' . $request->field;

        // Create transaction
        MyAutoTransaction::create([
            'my_auto_daily_log_id' => $log->id,
            'type'          => 'expense',
            'amount'        => $request->amount,
            'expense_field' => $field,
            'note'          => $request->note,
        ]);

        // Update summary column
        $log->increment($field, $request->amount);

        return response()->json(['success' => true]);
    }

    public function updateField(Request $request)
    {
        $request->validate([
            'log_id' => 'required|exists:my_auto_daily_logs,id',
            'field'  => 'required|string',
            'value'  => 'required|numeric|min:0',
        ]);

        $log   = MyAutoDailyLog::findOrFail($request->log_id);
        $field = $request->field;
        $value = round($request->value, 2);

        if ($field === 'current_meter') {

            if ($value < $log->starting_meter) {
                return response()->json([
                    'error' => 'Current Meter cannot be less than starting meter (' . $log->starting_meter . ')'
                ], 422);
            }

            $log->current_meter = $value;
            $log->save();
        } elseif ($field === 'trip_income' && $value > 0) {

            MyAutoTransaction::create([
                'my_auto_daily_log_id' => $log->id,
                'type' => 'income',
                'amount' => $value,
            ]);

            $log->increment('trip_income', $value);
        } elseif (in_array($field, [
            'expense_1',
            'expense_2',
            'expense_3',
            'expense_4',
            'expense_5'
        ]) && $value > 0) {

            MyAutoTransaction::create([
                'my_auto_daily_log_id' => $log->id,
                'type' => 'expense',
                'amount' => $value,
                'expense_field' => $field,
            ]);

            $log->increment($field, $value);
        }

        $todayIncome  = $log->trip_income;
        $todayExpense = $log->expense_1
            + $log->expense_2
            + $log->expense_3
            + $log->expense_4
            + $log->expense_5;

        $todayProfit  = $todayIncome - $todayExpense;

        $totalIncome = MyAutoDailyLog::where('my_auto_setting_id', $log->my_auto_setting_id)
            ->sum('trip_income');

        $totalExpense = MyAutoDailyLog::where('my_auto_setting_id', $log->my_auto_setting_id)
            ->selectRaw('SUM(expense_1 + expense_2 + expense_3 + expense_4 + expense_5) as total')
            ->value('total') ?? 0;

        return response()->json([
            'todayIncome'  => number_format($todayIncome, 2),
            'todayExpense' => number_format($todayExpense, 2),
            'todayProfit'  => number_format($todayProfit, 2),

            'totalIncome'  => number_format($totalIncome, 2),
            'totalExpense' => number_format($totalExpense, 2),
            'totalProfit'  => number_format($totalIncome - $totalExpense, 2),

            'currentMeter' => number_format($log->current_meter ?? 0, 2),
            'todayMileage' => number_format($log->current_meter ? $log->current_meter - $log->starting_meter : 0, 2),
            'mileageToday' => number_format($log->current_meter ? $log->current_meter - $log->starting_meter : 0, 2),
            'mileageTotal' => number_format(
                ($log->current_meter ?? $log->starting_meter) - $log->myAutoSetting->starting_meter,
                2
            ),
        ]);
    }

    public function updateMultiple(Request $request)
    {
        $request->validate([
            'log_id' => 'required|exists:my_auto_daily_logs,id',
            'fields' => 'required|array',
            'notes'  => 'nullable|array',
        ]);

        try {
            $log = MyAutoDailyLog::findOrFail($request->log_id);
            $notes = $request->notes ?? [];

            $logUpdates = [];

            foreach ($request->fields as $field => $value) {
                $value = round($value, 2);

                if ($field === 'current_meter') {
                    if ($value < $log->starting_meter) {
                        return response()->json([
                            'success' => false,
                            'msg' => 'Current Meter cannot be less than starting meter (' . $log->starting_meter . ')'
                        ], 422);
                    }
                    $logUpdates[$field] = $value;
                }
                elseif ($field === 'trip_income' && $value >= 0) {
                    $logUpdates[$field] = $value;
                }
                elseif (in_array($field, [
                    'expense_1', 'expense_2', 'expense_3', 'expense_4', 'expense_5'
                ]) && $value >= 0) {
                    $logUpdates[$field] = $value;
                }
            }

            \DB::transaction(function () use ($log, $logUpdates, $notes) {
                if (!empty($logUpdates)) {
                    $log->fill($logUpdates);
                    $log->save();
                }

                if (array_key_exists('trip_income', $logUpdates)) {
                    MyAutoTransaction::where('my_auto_daily_log_id', $log->id)
                        ->where('type', 'income')
                        ->delete();

                    if ($log->trip_income > 0) {
                        MyAutoTransaction::create([
                            'my_auto_daily_log_id' => $log->id,
                            'type' => 'income',
                            'amount' => $log->trip_income,
                        ]);
                    }
                }

                foreach (['expense_1', 'expense_2', 'expense_3', 'expense_4', 'expense_5'] as $expenseField) {
                    if (!array_key_exists($expenseField, $logUpdates)) {
                        continue;
                    }

                    MyAutoTransaction::where('my_auto_daily_log_id', $log->id)
                        ->where('type', 'expense')
                        ->where('expense_field', $expenseField)
                        ->delete();

                    $amount = (float) $log->{$expenseField};

                    if ($amount > 0) {
                        MyAutoTransaction::create([
                            'my_auto_daily_log_id' => $log->id,
                            'type' => 'expense',
                            'amount' => $amount,
                            'expense_field' => $expenseField,
                            'note' => !empty($notes[$expenseField]) ? $notes[$expenseField] : null,
                        ]);
                    }
                }
            });

            $log->refresh();

            $todayExpense = $log->expense_1 + $log->expense_2 + $log->expense_3 + $log->expense_4 + $log->expense_5;
            $todayProfit = $log->trip_income - $todayExpense;
            $todayMileage = $log->current_meter ? $log->current_meter - $log->starting_meter : 0;

            $totalIncome = MyAutoDailyLog::where('my_auto_setting_id', $log->my_auto_setting_id)
                ->sum('trip_income');
            $totalExpense = MyAutoDailyLog::where('my_auto_setting_id', $log->my_auto_setting_id)
                ->selectRaw('SUM(expense_1 + expense_2 + expense_3 + expense_4 + expense_5) as total')
                ->value('total') ?? 0;
            $totalMileage = ($log->current_meter ?? $log->starting_meter) - $log->myAutoSetting->starting_meter;

            return response()->json([
                'success' => true,
                'msg' => __('lang_v1.success'),
                'todayIncome' => number_format($log->trip_income, 2),
                'todayExpense' => number_format($todayExpense, 2),
                'todayProfit' => number_format($todayProfit, 2),
                'currentMeter' => number_format($log->current_meter ?? 0, 2),
                'todayMileage' => number_format($todayMileage, 2),
                'totalIncome' => number_format($totalIncome, 2),
                'totalExpense' => number_format($totalExpense, 2),
                'totalProfit' => number_format($totalIncome - $totalExpense, 2),
                'totalMileage' => number_format($totalMileage, 2),
                'fieldValues' => [
                    'trip_income' => number_format($log->trip_income ?? 0, 2, '.', ''),
                    'expense_1' => number_format($log->expense_1 ?? 0, 2, '.', ''),
                    'expense_2' => number_format($log->expense_2 ?? 0, 2, '.', ''),
                    'expense_3' => number_format($log->expense_3 ?? 0, 2, '.', ''),
                    'expense_4' => number_format($log->expense_4 ?? 0, 2, '.', ''),
                    'expense_5' => number_format($log->expense_5 ?? 0, 2, '.', ''),
                    'current_meter' => number_format($log->current_meter ?? 0, 2, '.', ''),
                ],
            ]);
        } catch (\Exception $e) {
            \Log::emergency(
                'File: ' . $e->getFile() .
                    ' Line: ' . $e->getLine() .
                    ' Message: ' . $e->getMessage()
            );

            return response()->json([
                'success' => false,
                'msg' => __('messages.something_went_wrong')
            ]);
        }
    }

    public function verifyPasscode(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id     = auth()->user()->id;

        $setting = MyAutoSetting::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->first();

        if (!$setting || $setting->passcode === null || $setting->passcode == $request->passcode) {
            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'msg' => 'Invalid passcode']);
    }

    public function todayIncomeDetails($logId)
    {
        $log = MyAutoDailyLog::findOrFail($logId);
        $transactions = MyAutoTransaction::where('my_auto_daily_log_id', $logId)
            ->where('type', 'income')
            ->orderBy('created_at', 'asc')
            ->get();

        $total = $transactions->sum('amount');

        return response()->json([
            'today_date' => now()->format('d-m-Y'),
            'total' => number_format($total, 2),
            'daily_starting_meter' => number_format($log->starting_meter ?? 0, 2, '.', ','),
            'daily_closing_meter' => number_format($log->current_meter ?? 0, 2, '.', ','),
            'details' => $transactions->map(function ($t) use ($log, $transactions) {
                $transactionIndex = $transactions->search($t);
                $totalTransactions = $transactions->count();
                
                // Calculate meter readings for this transaction
                $dayStartingMeter = $log->starting_meter ?? 0;
                $dayCurrentMeter = $log->current_meter ?? 0;
                
                if ($totalTransactions == 1) {
                    $startingMeter = $dayStartingMeter;
                    $closingMeter = $dayCurrentMeter;
                } else {
                    $totalMileage = $dayCurrentMeter - $dayStartingMeter;
                    $mileagePerTransaction = $totalMileage / $totalTransactions;
                    
                    if ($transactionIndex == 0) {
                        $startingMeter = $dayStartingMeter;
                    } else {
                        $startingMeter = $dayStartingMeter + ($mileagePerTransaction * $transactionIndex);
                    }
                    
                    if ($transactionIndex == $totalTransactions - 1) {
                        $closingMeter = $dayCurrentMeter;
                    } else {
                        $closingMeter = $startingMeter + $mileagePerTransaction;
                    }
                }
                
                return [
                    'date_time' => $t->created_at->format('d-m-Y H:i'),
                    'starting_meter' => number_format($startingMeter, 2),
                    'closing_meter' => number_format($closingMeter, 2),
                    'amount' => number_format($t->amount, 2),
                    'description' => $t->description ?? 'Income Transaction',
                    'transaction_type' => $t->transaction_type ?? 'General Income',
                    'payment_method' => $t->payment_method ?? 'Cash',
                    'reference' => $t->reference ?? '',
                ];
            }),
        ]);
    }

    public function todayExpenseDetails($logId)
    {
        $log = MyAutoDailyLog::findOrFail($logId);
        $transactions = MyAutoTransaction::where('my_auto_daily_log_id', $logId)
            ->where('type', 'expense')
            ->orderBy('created_at', 'asc')
            ->get();

        $expenseMap = [
            'expense_1' => 'Petrol / Diesel',
            'expense_2' => 'Oil',
            'expense_3' => 'Repairs',
            'expense_4' => 'Meals',
            'expense_5' => 'Others',
        ];

        return response()->json([
            'date' => now()->format('d-m-Y'),
            'expenses' => $transactions->map(function ($t) use ($expenseMap, $log) {
                return [
                    'date_time'   => $t->created_at->format('d-m-Y H:i'),
                    'type'   => $expenseMap[$t->expense_field] ?? 'Unknown',
                    'amount' => number_format($t->amount, 2),
                    'note'   => $t->note,
                    'starting_meter' => number_format($log->starting_meter ?? 0, 2, '.', ','),
                    'closing_meter' => number_format($log->current_meter ?? 0, 2, '.', ','),
                ];
            }),
        ]);
    }

    public function changePasscode(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'passcode'              => 'required|string|min:4|max:8',
            'passcode_confirmation' => 'required|same:passcode',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $user_id     = auth()->id();

        $setting = MyAutoSetting::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->firstOrFail();

        $setting->update(['passcode' => $request->passcode]);

        $response = response()->json(['success' => true]);

        if ($setting->is_sms_enabled && $setting->sms_mobile_numbers) {
            if (function_exists('fastcgi_finish_request')) {
                $response->send();
                fastcgi_finish_request();
            }

            $business    = Business::find($business_id);
            $smsSettings = empty($business->sms_settings)
                ? $this->businessUtil->defaultSmsSettings()
                : $business->sms_settings;

            $this->businessUtil->sendSms([
                'business_id'   => $business_id,
                'mobile_number' => $setting->sms_mobile_numbers,
                'sms_body'      => 'My Auto system Passcode Changed. Your New Passcode is ' . $request->passcode,
                'sms_settings'  => $smsSettings,
            ], 'Passcode Change');
        }

        return $response;
    }

public function pastDetails(Request $request, $logId)
{
    $log = MyAutoDailyLog::findOrFail($logId);

    $query = MyAutoTransaction::whereHas('dailyLog', function ($q) use ($log) {
        $q->where('my_auto_setting_id', $log->my_auto_setting_id);
    });

    // Filter type
    if ($request->filled('type') && $request->type !== 'all') {
        $query->where('type', $request->type);
    }

    // Date range filter
    if ($request->filled('start') && $request->filled('end')) {
        $query->whereBetween('created_at', [
            Carbon::parse($request->start)->startOfDay(),
            Carbon::parse($request->end)->endOfDay()
        ]);
    }

    // Search filter
    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function ($q) use ($search) {
            $q->where('note', 'like', "%{$search}%")
                ->orWhere('amount', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%");
        });
    }

    // PAGINATION – respects ?per_page= param (default 25, max 500)
    $perPage = min((int) $request->input('per_page', 25), 500);
    $perPage = max($perPage, 1);

    // OPTIMIZATION 1: Select only needed fields
    $transactions = $query
        ->with(['dailyLog:id,starting_meter,current_meter'])
        ->select(['id', 'my_auto_daily_log_id', 'type', 'amount', 'note', 'created_at'])
        ->orderBy('created_at', 'desc')
        ->paginate($perPage);

    // OPTIMIZATION 2: Calculate running profit with SQL instead of PHP
    // Get all transaction IDs in this page
    $transactionIds = $transactions->getCollection()->pluck('id')->toArray();
    
    // Get cumulative profit up to the first transaction in this page
    $cumulativeProfitBeforePage = MyAutoTransaction::whereHas('dailyLog', function ($q) use ($log) {
        $q->where('my_auto_setting_id', $log->my_auto_setting_id);
    })
    ->when($request->filled('type') && $request->type !== 'all', function ($q) use ($request) {
        $q->where('type', $request->type);
    })
    ->when($request->filled('start') && $request->filled('end'), function ($q) use ($request) {
        $q->whereBetween('created_at', [
            Carbon::parse($request->start)->startOfDay(),
            Carbon::parse($request->end)->endOfDay()
        ]);
    })
    ->where('created_at', '>', $transactions->getCollection()->last()->created_at ?? now())
    ->selectRaw('SUM(CASE WHEN type = "income" THEN amount ELSE -amount END) as cumulative')
    ->value('cumulative') ?? 0;

    // OPTIMIZATION 3: Process data efficiently
    $runningProfit = $cumulativeProfitBeforePage;
    
    $data = $transactions->getCollection()->map(function ($t) use (&$runningProfit) {
        // Use simple math instead of condition for each row
        $runningProfit += ($t->type === 'income' ? $t->amount : -$t->amount);
        
        return [
            'date'       => $t->created_at->format('d-m-Y H:i'),
            'type'       => ucfirst($t->type),
            'amount'     => number_format($t->amount, 2, '.', ','),
            'net_profit' => number_format($runningProfit, 2, '.', ','),
            'note'       => $t->note,
            'starting_meter' => number_format(optional($t->dailyLog)->starting_meter ?? 0, 2, '.', ','),
            'closing_meter' => number_format(optional($t->dailyLog)->current_meter ?? 0, 2, '.', ','),
        ];
    });

    return response()->json([
        'data' => $data,
        'pagination' => [
            'current_page' => $transactions->currentPage(),
            'last_page'    => $transactions->lastPage(),
            'total'        => $transactions->total(),
        ]
    ]);
}
}
