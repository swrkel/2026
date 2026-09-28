<?php

namespace Modules\StockTakingNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $recipientRule = $this->input('channel') === 'email'
            ? ['required', 'email:rfc', 'max:255']
            : ['required', 'string', 'max:40', 'regex:/^[0-9+()\-\s]+$/'];

        return [
            'channel' => 'required|in:sms,email,whatsapp',
            'recipient' => $recipientRule,
            'recipient_name' => 'nullable|string|max:160',
            'document_type' => 'required|in:summary,count_sheet,variance,reconciliation',
            'expiry_hours' => 'nullable|integer|min:1|max:8760',
            'download_allowed' => 'nullable|boolean',
            'message' => 'nullable|string|max:3000',
        ];
    }
}
