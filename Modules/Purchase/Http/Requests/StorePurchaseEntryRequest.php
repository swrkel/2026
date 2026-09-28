<?php

namespace Modules\Purchase\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Modules\Purchase\Utils\PurchaseAccessUtil;
use Modules\Purchase\Utils\PurchaseDateNumberUtil;

class StorePurchaseEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(PurchaseAccessUtil::class)->canCreate();
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('contact_id') && $this->filled('supplier_id')) {
            $this->merge(['contact_id' => $this->input('supplier_id')]);
        }

        // Legacy Products/New records can legitimately have no configured unit.
        // The Add Purchase UI represents that compatibility state as 0/"0".
        // sub_unit_id is nullable, so normalise non-positive placeholders to null
        // before Laravel applies the min:1 rule. Real unit/sub-unit IDs are kept.
        $purchases = (array) $this->input('purchases', []);
        foreach ($purchases as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $subUnitId = $line['sub_unit_id'] ?? null;
            if ($subUnitId === '' || $subUnitId === null || (is_numeric($subUnitId) && (int) $subUnitId <= 0)) {
                $purchases[$index]['sub_unit_id'] = null;
            }
        }

        if ($purchases !== []) {
            $this->merge(['purchases' => $purchases]);
        }
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'invoice_no' => ['required', 'string', 'max:100'],
            'ref_no' => ['nullable', 'string', 'max:255'],
            'order_no' => ['nullable', 'string', 'max:100'],
            'linked_purchase_order_id' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:received,pending,ordered'],
            'contact_id' => ['required', 'integer', 'min:1'],
            'location_id' => ['required', 'integer', 'min:1'],
            'sw_shift_no' => ['nullable', 'string', 'max:60'],
            'store_id' => ['required', 'integer', 'min:1'],
            'transaction_date' => ['required', 'string', 'max:50'],
            'invoice_date' => ['required', 'string', 'max:50'],
            'pay_term_number' => ['nullable', 'integer', 'min:0'],
            'pay_term_type' => ['nullable', 'in:days,months'],
            'is_vat' => ['nullable', 'boolean'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx', 'max:5120'],
            'shipping_details' => ['nullable', 'string', 'max:1000'],
            'additional_notes' => ['nullable', 'string', 'max:5000'],
            'discount_type' => ['nullable', 'in:fixed,percentage'],
            'discount_amount' => ['nullable'],
            'tax_id' => ['nullable', 'integer', 'min:1'],
            'shipping_charges' => ['nullable'],
            'price_adjustment' => ['nullable'],
            'exchange_rate' => ['nullable'],
            'apply_free_product_total' => ['nullable', 'boolean'],
            'save_action' => ['nullable', 'in:list,view,new'],

            'purchases' => ['required', 'array', 'min:1'],
            'purchases.*.product_id' => ['required', 'integer', 'min:1'],
            'purchases.*.variation_id' => ['required', 'integer', 'min:1'],
            'purchases.*.product_variation_id' => ['nullable', 'integer'],
            'purchases.*.product_unit_id' => ['nullable', 'integer'],
            'purchases.*.sub_unit_id' => ['nullable', 'integer', 'min:1'],
            'purchases.*.unit_multiplier' => ['nullable'],
            'purchases.*.quantity' => ['required'],
            'purchases.*.free_qty' => ['nullable'],
            'purchases.*.pp_without_discount' => ['required'],
            // CH1 IS2115: the visible purchase-cost fields are intentionally
            // formatted to four decimals. These optional hidden values retain
            // the full calculation precision used by the browser totals.
            'purchases.*.pp_without_discount_precise' => ['nullable'],
            'purchases.*.discount_type' => ['nullable', 'in:fixed,percentage'],
            'purchases.*.discount_value' => ['nullable'],
            'purchases.*.discount_percent' => ['nullable'],
            'purchases.*.discount_amount' => ['nullable'],
            'purchases.*.purchase_line_tax_id' => ['nullable', 'integer'],
            'purchases.*.item_tax' => ['nullable'],
            'purchases.*.purchase_price' => ['required'],
            'purchases.*.purchase_price_inc_tax' => ['required'],
            'purchases.*.purchase_price_inc_tax_precise' => ['nullable'],
            'purchases.*.profit_percent' => ['nullable'],
            'purchases.*.default_sell_price' => ['nullable'],
            'purchases.*.lot_number' => ['nullable', 'string', 'max:256'],
            'purchases.*.mfg_date' => ['nullable', 'string', 'max:50'],
            'purchases.*.exp_date' => ['nullable', 'string', 'max:50'],

            'tanks' => ['nullable', 'array'],
            'tanks.*' => ['nullable', 'array'],
            'tanks.*.*' => ['nullable', 'array'],
            'tanks.*.*.qty' => ['nullable', 'numeric', 'min:0'],
            'tanks.*.*.instock_qty' => ['nullable', 'numeric'],

            'payments' => ['nullable', 'array'],
            'payments.*.method' => ['nullable', 'string', 'max:100'],
            'payments.*.amount' => ['nullable'],
            'payments.*.account_id' => ['nullable', 'integer'],
            'payments.*.paid_on' => ['nullable', 'string', 'max:50'],
            'payments.*.reference_no' => ['nullable', 'string', 'max:100'],
            'payments.*.payment_ref_no' => ['nullable', 'string', 'max:191'],
            'payments.*.cheque_number' => ['nullable', 'string', 'max:100'],
            'payments.*.cheque_date' => ['nullable', 'string', 'max:50'],
            'payments.*.bank_name' => ['nullable', 'string', 'max:255'],
            'payments.*.bank_account_number' => ['nullable', 'string', 'max:100'],
            'payments.*.transfer_date' => ['nullable', 'string', 'max:50'],
            'payments.*.card_transaction_number' => ['nullable', 'string', 'max:100'],
            'payments.*.card_number' => ['nullable', 'string', 'max:30'],
            'payments.*.card_type' => ['nullable', 'string', 'max:50'],
            'payments.*.card_holder_name' => ['nullable', 'string', 'max:191'],
            'payments.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $numbers = app(PurchaseDateNumberUtil::class);
            $validLines = 0;

            foreach ((array) $this->input('purchases', []) as $index => $line) {
                $qty = $numbers->number($line['quantity'] ?? 0);
                $cost = $numbers->number($line['pp_without_discount'] ?? 0);
                if ($qty > 0 && $cost >= 0) {
                    $validLines++;
                } else {
                    $validator->errors()->add("purchases.$index.quantity", 'Every product row must have a quantity greater than zero.');
                }

                $mfg = trim((string) ($line['mfg_date'] ?? ''));
                $exp = trim((string) ($line['exp_date'] ?? ''));
                if ($mfg !== '' && $exp !== '') {
                    try {
                        if ($numbers->dateTime($exp)->lt($numbers->dateTime($mfg))) {
                            $validator->errors()->add("purchases.$index.exp_date", 'Expiry date cannot be earlier than the manufacturing date.');
                        }
                    } catch (\Throwable) {
                        $validator->errors()->add("purchases.$index.exp_date", 'Enter valid manufacturing and expiry dates.');
                    }
                }
            }

            if ($validLines === 0) {
                $validator->errors()->add('purchases', 'Add at least one valid product to the purchase.');
            }

            foreach ((array) $this->input('payments', []) as $index => $payment) {
                $amount = $numbers->number($payment['amount'] ?? 0);
                $method = (string) ($payment['method'] ?? '');
                if ($amount <= 0 || $method === '') {
                    continue;
                }

                if ($method !== 'credit_purchase' && empty($payment['account_id'])) {
                    $validator->errors()->add("payments.$index.account_id", 'Select the payment account linked to the selected payment method.');
                }
            }
        });
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'contact_id.required' => 'Please select a supplier.',
            'location_id.required' => 'Please select a business location.',
            'store_id.required' => 'Please select a store.',
            'purchases.required' => 'Add at least one product.',
            'purchases.min' => 'Add at least one product.',
            'document.max' => 'The purchase document must not exceed 5 MB.',
        ];
    }
}
