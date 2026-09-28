<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroPDNew\Http\Requests\PaymentStoreRequest;
use Modules\PetroPDNew\Http\Requests\PaymentUpdateRequest;
use Modules\PetroPDNew\Http\Requests\ReasonRequest;
use Modules\PetroPDNew\Services\PdnewBusinessFeatureService;
use Modules\PetroPDNew\Services\Settlement\PdnewPaymentService;

class SettlementPaymentController extends PdnewController
{
    public function store(PaymentStoreRequest $request, int $settlement, PdnewPaymentService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('payments', $this->context->businessId());

        try {
            $model = $this->settlement($settlement);
            $data = $request->validated();

            if ($service->duplicateReference(
                $this->context->businessId(),
                (string) $data['payment_type'],
                $data['reference_no'] ?? null
            )) {
                return back()->withInput()->with('error', 'This payment reference is already used in Petro PD-New.');
            }

            $service->create($model, $data, $this->context->userId());
            return back()->with('success', 'Settlement payment saved.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function update(PaymentUpdateRequest $request, int $payment, PdnewPaymentService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('payments', $this->context->businessId());

        try {
            $model = $this->payment($payment);
            $data = $request->validated();

            if ($service->duplicateReference(
                $this->context->businessId(),
                (string) $model->payment_type,
                $data['reference_no'] ?? null,
                $model->id
            )) {
                return back()->withInput()->with('error', 'This payment reference is already used in Petro PD-New.');
            }

            $service->update($model, $data);
            return back()->with('success', 'Settlement payment updated.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function void(ReasonRequest $request, int $payment, PdnewPaymentService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('payments', $this->context->businessId());

        try {
            $service->void($this->payment($payment), $this->context->userId(), (string) $request->validated('reason'));
            return back()->with('success', 'Settlement payment voided.');
        } catch (\Throwable $exception) {
            return $this->error($exception);
        }
    }

    public function checkReference(Request $request, PdnewPaymentService $service, PdnewBusinessFeatureService $features)
    {
        $features->authorizeSettlementTab('payments', $this->context->businessId());

        $request->validate(['payment_type' => 'required|string|max:40', 'reference_no' => 'nullable|string|max:191', 'ignore_id' => 'nullable|integer']);
        return response()->json([
            'duplicate' => $service->duplicateReference(
                $this->context->businessId(),
                (string) $request->payment_type,
                $request->reference_no,
                (int) $request->ignore_id ?: null
            ),
        ]);
    }
}
