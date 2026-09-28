<?php

namespace Modules\PriceChangeNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePriceChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'reason' => ['nullable', 'string', 'max:5000'],
            'effective_at' => ['nullable', 'date'],
            'stock_price_mode' => ['required', Rule::in(['all_stock'])],
            'application_scope' => ['required', Rule::in(['business_base', 'location_price_groups'])],
            'location_ids' => ['required', 'array', 'min:1'],
            'location_ids.*' => ['integer', 'min:1'],
            'lines_json' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lines = json_decode((string) $this->input('lines_json'), true);
            if (! is_array($lines) || $lines === []) {
                $validator->errors()->add('lines_json', 'Add at least one product price line.');
                return;
            }

            if (count($lines) > (int) config('pricechangenew.max_lines_per_draft', 500)) {
                $validator->errors()->add('lines_json', 'Too many price lines were submitted in one draft.');
                return;
            }

            foreach ($lines as $index => $line) {
                if (empty($line['variation_id'])) {
                    $validator->errors()->add('lines_json', 'Line ' . ($index + 1) . ' has no product variation.');
                }
                if (! isset($line['new_sell_price']) || ! is_numeric($line['new_sell_price']) || (float) $line['new_sell_price'] < 0) {
                    $validator->errors()->add('lines_json', 'Line ' . ($index + 1) . ' needs a valid new selling price.');
                }
                if (! in_array($line['sell_price_basis'] ?? '', ['ex_tax', 'inc_tax'], true)) {
                    $validator->errors()->add('lines_json', 'Line ' . ($index + 1) . ' has an invalid selling-price basis.');
                }
                if (($line['new_purchase_price'] ?? '') !== ''
                    && (! is_numeric($line['new_purchase_price']) || (float) $line['new_purchase_price'] < 0)) {
                    $validator->errors()->add('lines_json', 'Line ' . ($index + 1) . ' has an invalid new purchase price.');
                }
                if (($line['new_purchase_price'] ?? '') !== ''
                    && ! in_array($line['purchase_price_basis'] ?? '', ['ex_tax', 'inc_tax'], true)) {
                    $validator->errors()->add('lines_json', 'Line ' . ($index + 1) . ' has an invalid purchase-price basis.');
                }
                if ($this->input('application_scope') === 'location_price_groups'
                    && ($line['new_purchase_price'] ?? '') !== '') {
                    $validator->errors()->add('application_scope', 'Location price groups can change selling prices only. Remove new purchase prices or use Business base price scope.');
                }
            }
        });
    }

    /** @return array<string, mixed> */
    public function priceChangeData(): array
    {
        $validated = $this->validated();
        $validated['lines'] = json_decode((string) $validated['lines_json'], true) ?: [];
        unset($validated['lines_json']);

        return $validated;
    }
}
