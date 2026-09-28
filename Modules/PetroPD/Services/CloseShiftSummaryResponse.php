<?php

namespace Modules\PetroPD\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class CloseShiftSummaryResponse
{
    public const SESSION_KEY = 'petropd.pumper_dashboard.last_closed_shift_summary';

    public function __construct(
        private readonly Request $request
    ) {
    }

    /**
     * Store the completed shift report and return the correct response.
     *
     * Normal form submission:
     *     Redirects directly to the redesigned report.
     *
     * AJAX/JSON submission:
     *     Returns summary_url. The supplied JavaScript patch redirects
     *     the browser to that URL.
     */
    public function respond(array $report): JsonResponse|RedirectResponse
    {
        $report = $this->normalize($report);

        Validator::make($report, [
            'business_name' => ['required', 'string'],
            'date' => ['required'],
            'shift_number' => ['required'],
            'operator_name' => ['required', 'string'],

            'close_shift.total_closed_pump_sales' => ['nullable', 'numeric'],
            'close_shift.total_payments' => ['nullable', 'numeric'],
            'close_shift.balance_to_settle' => ['nullable', 'numeric'],
            'close_shift.current_balance_to_operator' => ['nullable', 'numeric'],

            'shift_details.shift_closed' => ['required'],
            'shift_details.closed_pumps' => ['nullable'],
            'shift_details.total_other_sales' => ['nullable', 'numeric'],
            'shift_details.balance_to_settle' => ['nullable', 'numeric'],

            'payment_summary.cash' => ['nullable', 'numeric'],
            'payment_summary.credit_sales' => ['nullable', 'numeric'],
            'payment_summary.credit_cards' => ['nullable', 'numeric'],
            'payment_summary.cheque_sales' => ['nullable', 'numeric'],
            'payment_summary.total' => ['nullable', 'numeric'],

            'cash_breakdown' => ['array'],
            'credit_sales_details' => ['array'],
        ])->validate();

        $this->request->session()->put(self::SESSION_KEY, $report);

        $summaryUrl = route(
            'petropd.pumper-dashboard.close-shift.summary'
        );

        if ($this->request->expectsJson() || $this->request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Shift closed successfully.',
                'summary_url' => $summaryUrl,
            ]);
        }

        return redirect()->to($summaryUrl);
    }

    /**
     * Make every expected report key available to the Blade view.
     */
    private function normalize(array $report): array
    {
        $paymentSummary = array_merge([
            'cash' => 0,
            'credit_sales' => 0,
            'credit_cards' => 0,
            'cheque_sales' => 0,
            'total' => null,
        ], (array) Arr::get($report, 'payment_summary', []));

        if ($paymentSummary['total'] === null) {
            $paymentSummary['total'] =
                (float) $paymentSummary['cash']
                + (float) $paymentSummary['credit_sales']
                + (float) $paymentSummary['credit_cards']
                + (float) $paymentSummary['cheque_sales'];
        }

        return [
            'business_name' => (string) Arr::get(
                $report,
                'business_name',
                config('app.name', 'Business Name')
            ),
            'date' => Arr::get($report, 'date', Carbon::now()),
            'shift_number' => Arr::get($report, 'shift_number', '—'),
            'operator_name' => (string) Arr::get(
                $report,
                'operator_name',
                '—'
            ),
            'printed_at' => Arr::get(
                $report,
                'printed_at',
                Carbon::now()
            ),

            'close_shift' => array_merge([
                'total_closed_pump_sales' => 0,
                'total_payments' => 0,
                'balance_to_settle' => 0,
                'current_balance_to_operator' => 0,
            ], (array) Arr::get($report, 'close_shift', [])),

            'shift_details' => array_merge([
                'shift_closed' => 1,
                'closed_pumps' => [],
                'total_other_sales' => 0,
                'balance_to_settle' => 0,
            ], (array) Arr::get($report, 'shift_details', [])),

            'payment_summary' => $paymentSummary,
            'cash_breakdown' => array_values(
                (array) Arr::get($report, 'cash_breakdown', [])
            ),
            'credit_sales_details' => array_values(
                (array) Arr::get($report, 'credit_sales_details', [])
            ),
        ];
    }
}
