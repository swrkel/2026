<?php
namespace Modules\StockTakingNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }
    public function rules(): array { return [
        'number_prefix'=>'required|string|max:20','quantity_decimals'=>'required|integer|min:0|max:6',
        'amount_decimals'=>'required|integer|min:0|max:6','default_count_mode'=>'required|in:blind,open',
        'require_approval'=>'nullable|boolean','require_recount_for_variance'=>'nullable|boolean',
        'post_to_shared_inventory'=>'nullable|boolean','share_link_expiry_hours'=>'required|integer|min:1|max:8760',
        'sms_endpoint'=>'nullable|url|max:1000','sms_token'=>'nullable|string|max:2000','sms_sender_id'=>'nullable|string|max:80',
        'whatsapp_endpoint'=>'nullable|url|max:1000','whatsapp_token'=>'nullable|string|max:2000','whatsapp_phone_number_id'=>'nullable|string|max:120',
    ]; }
}
