<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'event_key' => trim((string) $this->input('event_key')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'event_key' => 'required|string|max:80|regex:/^[a-z0-9_.-]+$/',
            'name' => 'required|string|max:191',
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string|max:20000',
            'channels' => 'required|array|min:1|max:4',
            'channels.*' => 'required|string|distinct|in:sms,email,whatsapp,system',
            'is_active' => 'required|boolean',
        ];
    }
}
