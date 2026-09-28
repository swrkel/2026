<?php

namespace Modules\PetroPDNew\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\PetroPDNew\Services\PdnewOperatorTabActionService;

class OperatorTabActionController extends PdnewController
{
    public function modal(Request $request, string $type, int $id, string $action, PdnewOperatorTabActionService $service)
    {
        $data = $service->modalData(
            $type,
            $id,
            $action,
            $this->context->businessId(),
            $this->context->locationId()
        );

        return view('petropdnew::operators.tab-action-modal', $data);
    }

    public function createDayEntry(Request $request, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->dayEntryRules(true));
        $service->createDayEntry($this->context->businessId(), $this->context->locationId(), $data);
        return $this->success('Pumper day entry saved.');
    }

    public function updateDayEntry(Request $request, int $entry, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->dayEntryRules(false));
        $service->updateDayEntry($this->context->businessId(), $this->context->locationId(), $entry, $data);
        return $this->success('Pumper day entry updated.');
    }

    public function voidDayEntry(Request $request, int $entry, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $service->voidDayEntry($this->context->businessId(), $this->context->locationId(), $entry, $data['reason']);
        return $this->success('Pumper day entry voided.');
    }

    public function createPayment(Request $request, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->paymentRules(true));
        $service->createPayment($this->context->businessId(), $this->context->locationId(), $data);
        return $this->success('Operator payment saved.');
    }

    public function updatePayment(Request $request, int $payment, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->paymentRules(false));
        $service->updatePayment($this->context->businessId(), $this->context->locationId(), $payment, $data);
        return $this->success('Operator payment updated.');
    }

    public function voidPayment(Request $request, int $payment, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $service->voidPayment($this->context->businessId(), $this->context->locationId(), $payment, $data['reason']);
        return $this->success('Operator payment voided.');
    }

    public function currentMeter(Request $request, int $assignment, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->meterRules());
        $service->recordCurrentMeter($this->context->businessId(), $this->context->locationId(), $assignment, $data);
        return $this->success('Current meter recorded.');
    }

    public function closePump(Request $request, int $assignment, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->meterRules());
        $service->closePump($this->context->businessId(), $this->context->locationId(), $assignment, $data);
        return $this->success('Pump closed.');
    }

    public function closeShift(Request $request, int $shift, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate([
            'confirmed' => ['accepted'],
            'note' => ['nullable', 'string', 'max:4000'],
        ]);
        $service->closeShift($this->context->businessId(), $this->context->locationId(), $shift, $data['note'] ?? null);
        return $this->success('Shift closed.');
    }

    public function createUnload(Request $request, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->unloadRules(true));
        $service->createUnload($this->context->businessId(), $this->context->locationId(), $data);
        return $this->success('Unload stock entry saved.');
    }

    public function updateUnload(Request $request, int $unload, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate($this->unloadRules(false));
        $service->updateUnload($this->context->businessId(), $this->context->locationId(), $unload, $data);
        return $this->success('Unload stock entry updated.');
    }

    public function voidUnload(Request $request, int $unload, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $service->voidUnload($this->context->businessId(), $this->context->locationId(), $unload, $data['reason']);
        return $this->success('Unload stock entry voided.');
    }

    public function voidVariance(Request $request, string $kind, int $record, PdnewOperatorTabActionService $service): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $service->voidVariance($this->context->businessId(), $this->context->locationId(), $kind, $record, $data['reason']);
        return $this->success(ucfirst($kind) . ' record voided.');
    }

    private function success(string $message): JsonResponse
    {
        return response()->json(['ok' => true, 'message' => $message]);
    }

    /** @return array<string,mixed> */
    private function dayEntryRules(bool $create): array
    {
        return [
            'shift_id' => [$create ? 'required' : 'nullable', 'integer', 'min:1'],
            'assignment_id' => ['nullable', 'integer', 'min:1'],
            'entry_type' => ['required', Rule::in(['note', 'incident', 'expense', 'deposit', 'meter', 'testing', 'other'])],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'starting_meter' => ['nullable', 'numeric', 'min:0'],
            'closing_meter' => ['nullable', 'numeric', 'min:0'],
            'testing_quantity' => ['nullable', 'numeric', 'min:0'],
            'entry_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:4000'],
            'edit_reason' => [$create ? 'nullable' : 'required', 'string', 'max:1000'],
        ];
    }

    /** @return array<string,mixed> */
    private function paymentRules(bool $create): array
    {
        return [
            'shift_id' => [$create ? 'required' : 'nullable', 'integer', 'min:1'],
            'payment_type' => ['required', Rule::in(['cash', 'card', 'cheque', 'credit', 'shortage', 'excess', 'other'])],
            'amount' => ['required', 'numeric', 'min:0'],
            'gross_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_id' => ['nullable', 'integer', 'min:1'],
            'reference_no' => ['nullable', 'string', 'max:191'],
            'slip_no' => ['nullable', 'string', 'max:100'],
            'cheque_no' => ['nullable', 'string', 'max:100'],
            'cheque_date' => ['nullable', 'date'],
            'transaction_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'edit_reason' => [$create ? 'nullable' : 'required', 'string', 'max:1000'],
        ];
    }

    /** @return array<string,mixed> */
    private function meterRules(): array
    {
        return [
            'meter' => ['required', 'numeric', 'min:0'],
            'testing_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_price' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /** @return array<string,mixed> */
    private function unloadRules(bool $create): array
    {
        return [
            'shift_id' => [$create ? 'required' : 'nullable', 'integer', 'min:1'],
            'store_id' => ['nullable', 'integer', 'min:1'],
            'supplier_id' => ['nullable', 'integer', 'min:1'],
            'bill_number' => ['nullable', 'string', 'max:191'],
            'supplier_reference' => ['nullable', 'string', 'max:191'],
            'unloaded_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:2000'],
            'edit_reason' => [$create ? 'nullable' : 'required', 'string', 'max:1000'],
            'lines' => ['required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['required', 'integer', 'min:1'],
            'lines.*.tank_id' => ['nullable', 'integer', 'min:1'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.dip_reading' => ['nullable', 'numeric', 'min:0'],
            'lines.*.current_stock' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
