<?php

namespace Modules\Loan\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Carbon\Carbon;
use Modules\Loan\Models\Loan;
use Modules\Loan\Models\LoanPromiseToPay;
use Modules\Loan\Models\LoanPromiseToPayLog;
use Modules\Loan\Models\LoanPromiseToPayFollowup;

class LoanPromiseToPayController extends Controller
{
    public function index(Request $request)
    {
        $business_id = session('business.id');

        $summary = LoanPromiseToPay::where('business_id', $business_id)
            ->selectRaw("
                COUNT(*) as total_ptp,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_ptp,
                SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) as broken_ptp,
                SUM(CASE WHEN status = 'kept' THEN 1 ELSE 0 END) as kept_ptp
            ")
            ->first();

        $total_ptp = (int) ($summary->total_ptp ?? 0);
        $open_ptp = (int) ($summary->open_ptp ?? 0);
        $broken_ptp = (int) ($summary->broken_ptp ?? 0);
        $kept_ptp = (int) ($summary->kept_ptp ?? 0);

        return view(
            'loan::promise_to_pay.index',
            compact(
                'total_ptp',
                'open_ptp',
                'broken_ptp',
                'kept_ptp'
            )
        );
    }

    public function ajaxData(Request $request)
    {
        $business_id = session('business.id');
        $currency_precision = session('business.currency_precision', 2);

        $draw = intval($request->get('draw'));
        $start = intval($request->get('start', 0));
        $length = intval($request->get('length', 10));

        if ($length <= 0 || $length > 500) {
            $length = 10;
        }

        $base_query = LoanPromiseToPay::query()
            ->where('business_id', $business_id);

        $records_total = (clone $base_query)->count();

        $query = LoanPromiseToPay::query()
            ->select([
                'id',
                'business_id',
                'location_id',
                'loan_id',
                'customer_id',
                'recovery_officer_id',
                'ptp_no',
                'promised_amount',
                'promised_payment_date',
                'status',
                'ptp_type',
                'source',
                'risk_level',
                'is_escalated'
            ])
            ->with([
                'customer:id,name',
                'location:id,name',
                'recoveryOfficer:id,first_name,last_name'
            ])
            ->where('business_id', $business_id);

        if (!empty($request->date_range)) {
            preg_match_all(
                '/\d{4}-\d{2}-\d{2}/',
                $request->date_range,
                $matches
            );

            if (!empty($matches[0]) && count($matches[0]) >= 2) {
                $start_date = $matches[0][0];
                $end_date = $matches[0][1];

                $query->whereBetween(
                    'promised_payment_date',
                    [$start_date, $end_date]
                );
            }
        }

        $search = trim((string) $request->input('search.value'));

        $amount_search = str_replace(',', '', $search);

        $is_amount_search = preg_match(
            '/^[0-9]+(\.[0-9]+)?$/',
            $amount_search
        );

        if (!empty($search)) {
            $query->where(function ($q) use ($search, $amount_search, $is_amount_search) {

                $q->where('ptp_no', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhere('ptp_type', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', '%' . $search . '%')
                    ->orWhere('risk_level', 'like', '%' . $search . '%')
                    ->orWhere('promised_payment_date', 'like', '%' . $search . '%');

                if ($is_amount_search) {
                    $q->orWhereRaw(
                        'CAST(promised_amount AS CHAR) LIKE ?',
                        ['%' . $amount_search . '%']
                    );
                }

                $q->orWhereHas('customer', function ($customer) use ($search) {
                    $customer->where('name', 'like', '%' . $search . '%');
                });

                $q->orWhereHas('location', function ($location) use ($search) {
                    $location->where('name', 'like', '%' . $search . '%');
                });

                $q->orWhereHas('recoveryOfficer', function ($officer) use ($search) {
                    $officer->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%');
                });
            });
        }

        $records_filtered = (clone $query)->count();

        $columns = [
            0 => 'ptp_no',
            1 => 'id',
            2 => 'loan_id',
            3 => 'location_id',
            4 => 'recovery_officer_id',
            5 => 'promised_amount',
            6 => 'promised_payment_date',
            7 => 'status',
            8 => 'is_escalated',
            9 => 'id'
        ];

        $order_column_index = intval($request->input('order.0.column', 0));
        $order_direction = $request->input('order.0.dir', 'desc');

        if (!in_array($order_direction, ['asc', 'desc'])) {
            $order_direction = 'desc';
        }

        $order_column = $columns[$order_column_index] ?? 'id';

        $records = $query
            ->orderBy($order_column, $order_direction)
            ->offset($start)
            ->limit($length)
            ->get();

        $data = [];

        foreach ($records as $record) {
            $customer_name = optional($record->customer)->name;

            if (empty($customer_name)) {
                $customer_name = '<span class="text-danger">Customer Not Linked</span>';
            } else {
                $customer_name = e($customer_name);
            }

            $officer_name = trim(
                optional($record->recoveryOfficer)->first_name . ' ' .
                optional($record->recoveryOfficer)->last_name
            );

            $data[] = [
                'ptp_no' => '<strong>' . e($record->ptp_no) . '</strong>',
                'customer' => $customer_name,
                'loan' => e($record->loan_id),
                'branch' => e(optional($record->location)->name),
                'officer' => e($officer_name),
                'promised_amount' => number_format(
                    (float) $record->promised_amount,
                    $currency_precision
                ),
                'promise_date' => e($record->promised_payment_date),
                'status' => $this->buildStatusLabel($record->status),
                'escalated' => $this->buildEscalatedLabel($record->is_escalated),
                'actions' => $this->buildActionsDropdown($record)
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $records_total,
            'recordsFiltered' => $records_filtered,
            'data' => $data
        ]);
    }

    private function buildStatusLabel($status)
    {
        if ($status == 'open') {
            return '<span class="label label-warning">Open</span>';
        }

        if ($status == 'broken') {
            return '<span class="label label-danger">Broken</span>';
        }

        if ($status == 'kept') {
            return '<span class="label label-success">Kept</span>';
        }

        return '<span class="label label-default">' .
            e(ucwords(str_replace('_', ' ', $status))) .
        '</span>';
    }

    private function buildEscalatedLabel($is_escalated)
    {
        if ($is_escalated) {
            return '<span class="label label-danger">Escalated</span>';
        }

        return '<span class="label label-primary">Normal</span>';
    }

    private function buildActionsDropdown($record)
    {
        $html = '
            <div class="dropdown erp-actions-dropdown">
                <button class="btn btn-primary btn-xs dropdown-toggle"
                        type="button"
                        data-toggle="dropdown">
                    Actions
                    <span class="caret"></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-right">
        ';

        if (auth()->user()->can('loan.promise_to_pay.view')) {
            $html .= '
                <li>
                    <a href="' . route('loan.promise.to.pay.show', $record->id) . '">
                        <i class="fa fa-eye text-primary"></i> View Details
                    </a>
                </li>
            ';
        }

        if (auth()->user()->can('loan.promise_to_pay.update')) {
            $html .= '
                <li>
                    <a href="' . route('loan.promise.to.pay.edit', $record->id) . '">
                        <i class="fa fa-pencil text-warning"></i> Edit
                    </a>
                </li>
            ';
        }

        if ($record->status == 'open') {
            if (auth()->user()->can('loan.promise_to_pay.mark_kept')) {
                $html .= '
                    <li>
                        <a href="#"
                           onclick="event.preventDefault(); document.getElementById(\'mark-kept-' . $record->id . '\').submit();">
                            <i class="fa fa-check text-success"></i> Mark Kept
                        </a>
                        <form id="mark-kept-' . $record->id . '"
                              action="' . route('loan.promise.to.pay.mark_kept', $record->id) . '"
                              method="POST"
                              style="display:none;">
                            ' . csrf_field() . '
                        </form>
                    </li>
                ';
            }

            if (auth()->user()->can('loan.promise_to_pay.mark_broken')) {
                $html .= '
                    <li>
                        <a href="#"
                           onclick="event.preventDefault(); document.getElementById(\'mark-broken-' . $record->id . '\').submit();">
                            <i class="fa fa-times text-danger"></i> Mark Broken
                        </a>
                        <form id="mark-broken-' . $record->id . '"
                              action="' . route('loan.promise.to.pay.mark_broken', $record->id) . '"
                              method="POST"
                              style="display:none;">
                            ' . csrf_field() . '
                        </form>
                    </li>
                ';
            }
        }

        $html .= '
                </ul>
            </div>
        ';

        return $html;
    }


public function create()
{
    $business_id = session('business.id');

    $customers = \App\Contact::where('business_id', $business_id)
        ->where('type', 'customer')
        ->orderBy('name')
        ->get();

    $loans = Loan::with('customer')
        ->where('business_id', $business_id)
        ->latest()
        ->limit(500)
        ->get();

    $officers = \App\User::where('business_id', $business_id)
        ->orderBy('first_name')
        ->get();

    return view(
        'loan::promise_to_pay.create',
        compact(
            'customers',
            'loans',
            'officers'
        )
    );
}

    public function store(Request $request)
    {
        try {
            $business_id = session('business.id');
            $promised_amount = str_replace(',', '', $request->promised_amount);

            $loan = Loan::with('customer')
                ->where('business_id', $business_id)
                ->where('id', $request->loan_id)
                ->firstOrFail();

            $ptp = LoanPromiseToPay::create([
                'business_id' => $business_id,
                'location_id' => $loan->location_id ?? null,
                'loan_id' => $loan->id,
                'loan_application_id' => $loan->loan_application_id ?? null,
                'customer_id' => $loan->contact_id ?? null,
                'recovery_officer_id' => $request->recovery_officer_id,
                'ptp_no' => 'PTP-' . strtoupper(uniqid()),
                'promised_amount' => $promised_amount,
                'promised_payment_date' => $request->promised_payment_date,
                'status' => 'open',
                'ptp_type' => $request->ptp_type,
                'source' => $request->source,
                'risk_level' => $request->risk_level,
                'remarks' => $request->remarks,
                'created_by' => auth()->id()
            ]);

            LoanPromiseToPayLog::create([
                'business_id' => $business_id,
                'promise_to_pay_id' => $ptp->id,
                'old_status' => null,
                'new_status' => 'open',
                'action_type' => 'created',
                'note' => 'PTP created',
                'created_by' => auth()->id()
            ]);

            LoanPromiseToPayFollowup::create([
                'business_id' => $business_id,
                'promise_to_pay_id' => $ptp->id,
                'followup_date' => Carbon::parse($request->promised_payment_date)
                    ->subDay()
                    ->format('Y-m-d'),
                'followup_status' => 'pending',
                'followup_note' => 'Automatic PTP reminder follow-up',
                'assigned_to' => $request->recovery_officer_id,
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->route('loan.promise.to.pay.index')
                ->with('success', 'Promise To Pay created successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $business_id = session('business.id');

        $record = LoanPromiseToPay::with([
                'customer',
                'loan.customer',
                'location',
                'recoveryOfficer',
                'logs.creator',
                'followups.assignedOfficer'
            ])
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        return view('loan::promise_to_pay.show', compact('record'));
    }

    public function edit($id)
    {
        $business_id = session('business.id');

        $record = LoanPromiseToPay::with([
                'customer',
                'loan.customer',
                'location',
                'recoveryOfficer'
            ])
            ->where('business_id', $business_id)
            ->where('id', $id)
            ->firstOrFail();

        $loans = Loan::with('customer')
            ->where('business_id', $business_id)
            ->limit(200)
            ->get();

        $officers = \App\User::where('business_id', $business_id)->get();

        return view(
            'loan::promise_to_pay.edit',
            compact('record', 'loans', 'officers')
        );
    }

    public function update(Request $request, $id)
    {
        try {
            $business_id = session('business.id');

            $record = LoanPromiseToPay::where('business_id', $business_id)
                ->where('id', $id)
                ->firstOrFail();

            $promised_amount = str_replace(',', '', $request->promised_amount);

            $new_data = [
                'promised_amount' => $promised_amount,
                'promised_payment_date' => $request->promised_payment_date,
                'recovery_officer_id' => $request->recovery_officer_id,
                'ptp_type' => $request->ptp_type,
                'source' => $request->source,
                'risk_level' => $request->risk_level,
                'remarks' => $request->remarks
            ];

            $old_data = $record->only(array_keys($new_data));

            foreach ($old_data as $key => $value) {
                if ($key == 'promised_amount') {
                    $old_data[$key] = (string) number_format((float) $value, 2, '.', '');
                    $new_data[$key] = (string) number_format((float) $new_data[$key], 2, '.', '');
                } else {
                    $old_data[$key] = (string) $value;
                    $new_data[$key] = (string) $new_data[$key];
                }
            }

            if ($old_data == $new_data) {
                return redirect()
                    ->back()
                    ->with('error', 'Nothing is changed to save.');
            }

            $record->update($new_data);

            LoanPromiseToPayLog::create([
                'business_id' => $record->business_id,
                'promise_to_pay_id' => $record->id,
                'old_status' => $record->status,
                'new_status' => $record->status,
                'action_type' => 'updated',
                'note' => 'PTP details updated',
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->route('loan.promise.to.pay.index')
                ->with('success', 'Promise To Pay updated successfully.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function markKept($id)
    {
        try {
            $business_id = session('business.id');

            $record = LoanPromiseToPay::where('business_id', $business_id)
                ->where('id', $id)
                ->firstOrFail();

            $record->update([
                'status' => 'kept',
                'actual_paid_amount' => $record->promised_amount,
                'actual_payment_date' => now()->format('Y-m-d')
            ]);

            LoanPromiseToPayLog::create([
                'business_id' => $record->business_id,
                'promise_to_pay_id' => $record->id,
                'old_status' => 'open',
                'new_status' => 'kept',
                'action_type' => 'kept',
                'note' => 'PTP marked as kept',
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('success', 'PTP marked as kept.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }

    public function markBroken($id)
    {
        try {
            $business_id = session('business.id');

            $record = LoanPromiseToPay::where('business_id', $business_id)
                ->where('id', $id)
                ->firstOrFail();

            $record->update([
                'status' => 'broken',
                'is_escalated' => 1,
                'escalated_at' => now()
            ]);

            LoanPromiseToPayLog::create([
                'business_id' => $record->business_id,
                'promise_to_pay_id' => $record->id,
                'old_status' => 'open',
                'new_status' => 'broken',
                'action_type' => 'broken',
                'note' => 'PTP marked as broken',
                'created_by' => auth()->id()
            ]);

            return redirect()
                ->back()
                ->with('success', 'PTP marked as broken.');

        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', $e->getMessage());
        }
    }
}