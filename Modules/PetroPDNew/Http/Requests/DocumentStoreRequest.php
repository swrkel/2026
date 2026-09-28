<?php

namespace Modules\PetroPDNew\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'document_type' => trim((string) $this->input('document_type')),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:191',
            'document_type' => 'required|in:supporting,bank_slip,approval,other',
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx,csv,txt',
        ];
    }
}
