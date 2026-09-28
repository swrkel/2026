<?php

namespace Modules\PumperDashboardNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettingsUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'location_id' => ['nullable', 'integer', 'min:1'],
            'integration_enabled' => ['nullable', 'boolean'],
            'integration_mode' => ['required', Rule::in(['petro_pd_new'])],
            'sync_during_operation' => ['nullable', 'boolean'],
            'require_clean_sync_before_close' => ['nullable', 'boolean'],
            'allow_operator_open_shift' => ['nullable', 'boolean'],
            'allow_close_with_open_pumps' => ['nullable', 'boolean'],
            'allow_close_with_pending_sync' => ['nullable', 'boolean'],
            'cash_denomination_enabled' => ['nullable', 'boolean'],
            'multi_card_enabled' => ['nullable', 'boolean'],
            'cheque_enabled' => ['nullable', 'boolean'],
            'credit_sale_enabled' => ['nullable', 'boolean'],
            'require_credit_dual_confirmation' => ['nullable', 'boolean'],
            'require_collection_before_close' => ['nullable', 'boolean'],
            'allow_payment_edit' => ['nullable', 'boolean'],
            'payment_edit_lock_minutes' => ['required', 'integer', 'between:0,10080'],
            'auto_logout_minutes' => ['required', 'integer', 'between:0,1440'],
            'receipt_paper_size' => ['required', Rule::in(['58mm', '80mm', 'A4'])],
            'default_store_id' => ['nullable', 'integer', 'min:1'],
            'amount_decimals' => ['required', 'integer', 'between:0,6'],
            'quantity_decimals' => ['required', 'integer', 'between:0,6'],
            'meter_decimals' => ['required', 'integer', 'between:0,6'],
            'shift_prefix' => ['required', 'string', 'max:30'],
            'payment_prefix' => ['required', 'string', 'max:30'],
            'other_sale_prefix' => ['required', 'string', 'max:30'],
            'unload_prefix' => ['required', 'string', 'max:30'],
            'settlement_prefix' => ['required', 'string', 'max:30'],
            'collection_prefix' => ['required', 'string', 'max:30'],
            'recovery_prefix' => ['required', 'string', 'max:30'],
            'commission_prefix' => ['required', 'string', 'max:30'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['integration_mode' => 'petro_pd_new', 'integration_enabled' => true]);
        foreach ([
            'integration_enabled', 'sync_during_operation', 'require_clean_sync_before_close',
            'allow_operator_open_shift', 'allow_close_with_open_pumps', 'allow_close_with_pending_sync',
            'cash_denomination_enabled', 'multi_card_enabled', 'cheque_enabled', 'credit_sale_enabled',
            'require_credit_dual_confirmation', 'require_collection_before_close', 'allow_payment_edit',
        ] as $field) {
            $this->merge([$field => $this->boolean($field)]);
        }
    }
}
