<?php

namespace Modules\PumperDashboardNew\Http\Controllers\Operator;

use Modules\PumperDashboardNew\Entities\PonePayment;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\PaymentStoreRequest;
use Modules\PumperDashboardNew\Http\Requests\VoidRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PonePaymentService;
use Modules\PumperDashboardNew\Services\PonePrintService;
use Modules\PumperDashboardNew\Services\PoneSettingsService;
use Modules\PumperDashboardNew\Services\PoneSharedMasterDataService;
use Modules\PumperDashboardNew\Services\PoneShiftTotalsService;

class PaymentController extends Controller
{
    public function __construct(
        private PoneContextService $context,
        private PonePaymentService $payments,
        private PoneSharedMasterDataService $masterData,
        private PoneSettingsService $settings,
        private PoneShiftTotalsService $totals,
        private PonePrintService $prints
    ) {}

    public function index()
    {
        $shift = $this->totals->refresh($this->context->shift());
        $payments = PonePayment::query()->where('shift_id', $shift->id)
            ->with(['creditSale.lines', 'cashDenominations', 'cardLines'])->latest('transaction_at')->latest('id')->get();
        return view('pumperdashboardnew::operator.payments.index', $this->formData($shift) + compact('payments'));
    }

    public function store(PaymentStoreRequest $request)
    {
        $payment = $this->payments->create($request->validated());
        return redirect()->route('pumper-dashboard-new.operator.payments.show', $payment)
            ->with('status', ['success' => 1, 'msg' => __('pumperdashboardnew::lang.payment_saved')]);
    }

    public function show(int $payment)
    {
        $shift = $this->context->shift(true);
        $payment = $this->find($payment, $shift->id);
        $business = $this->masterData->business($shift->business_id);
        $customers = $this->masterData->customers($shift->business_id, null, 10000)->keyBy('id');
        $products = $this->masterData->products($shift->business_id, $shift->location_id, null, 10000)->keyBy('id');
        return view('pumperdashboardnew::operator.payments.show', compact('shift', 'payment', 'business', 'customers', 'products'));
    }

    public function edit(int $payment)
    {
        $shift = $this->context->shift();
        $payment = $this->find($payment, $shift->id);
        abort_if($payment->payment_type === 'credit', 422, __('pumperdashboardnew::lang.credit_sale_locked'));
        return view('pumperdashboardnew::operator.payments.edit', $this->formData($shift) + compact('payment'));
    }

    public function update(PaymentStoreRequest $request, int $payment)
    {
        $payment = $this->payments->update($payment, $request->validated());
        return redirect()->route('pumper-dashboard-new.operator.payments.show', $payment)
            ->with('status', ['success' => 1, 'msg' => __('pumperdashboardnew::lang.payment_updated')]);
    }

    public function destroy(VoidRequest $request, int $payment)
    {
        $this->payments->void($payment, $request->validated('reason'));
        return $this->ok(__('pumperdashboardnew::lang.payment_voided'));
    }

    public function summary()
    {
        $shift = $this->totals->refresh($this->context->shift());
        $payments = PonePayment::query()->where('shift_id', $shift->id)->where('status', 'confirmed')
            ->selectRaw('payment_type, COUNT(*) AS records, SUM(amount) AS amount')
            ->groupBy('payment_type')->orderBy('payment_type')->get();
        return view('pumperdashboardnew::operator.payments.summary', compact('shift', 'payments'));
    }

    public function print(int $payment)
    {
        $shift = $this->context->shift(true);
        $payment = $this->find($payment, $shift->id);
        $copyType = $payment->printed_count > 0 ? 'reprint' : 'original';
        $paperSize = request('paper_size');
        $this->prints->record('payment', $payment, 'payment-receipt', $paperSize, $copyType);
        $business = $this->masterData->business($shift->business_id);
        $location = $this->masterData->location($shift->location_id);
        $customers = $this->masterData->customers($shift->business_id, null, 10000)->keyBy('id');
        $products = $this->masterData->products($shift->business_id, $shift->location_id, null, 10000)->keyBy('id');
        return view('pumperdashboardnew::operator.payments.print', compact('shift', 'payment', 'business', 'location', 'customers', 'products', 'copyType', 'paperSize'));
    }

    private function formData($shift): array
    {
        return [
            'shift' => $shift,
            'customers' => $this->masterData->customers($shift->business_id),
            'products' => $this->masterData->products($shift->business_id, $shift->location_id),
            'accounts' => $this->masterData->accounts($shift->business_id),
            'settings' => $this->settings->get($shift->business_id, $shift->location_id),
            'denominations' => config('pumperdashboardnew.cash_denominations', []),
        ];
    }

    private function find(int $id, int $shiftId): PonePayment
    {
        return PonePayment::query()->whereKey($id)->where('shift_id', $shiftId)
            ->where('business_id', $this->context->businessId())
            ->with(['creditSale.lines', 'cashDenominations', 'cardLines', 'editHistories'])->firstOrFail();
    }
}
