<?php
namespace Modules\ManagementReport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ShareReportRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'channel' => 'required|in:sms,email,whatsapp',
            'recipients' => 'required|array|min:1|max:50',
            'recipients.*' => 'required|string|max:190',
            'message' => 'nullable|string|max:1000',
            'expiry_hours' => 'nullable|integer|min:1|max:720',
            'attach_pdf' => 'nullable|boolean',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $channel = $this->input('channel');
            foreach ((array) $this->input('recipients', []) as $index => $recipient) {
                if ($channel === 'email' && !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $validator->errors()->add('recipients.' . $index, 'Please enter a valid email address.');
                }
                if (in_array($channel, ['sms', 'whatsapp'], true) && strlen(preg_replace('/[^0-9]/', '', $recipient)) < 7) {
                    $validator->errors()->add('recipients.' . $index, 'Please enter a valid mobile number.');
                }
            }
        });
    }
}
