<?php
namespace Modules\PumperDashboardNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class CollectionStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
        'collection_number'=>['nullable','string','max:100'], 'collection_at'=>['nullable','date'],
        'cash_amount'=>['nullable','numeric','min:0'], 'card_amount'=>['nullable','numeric','min:0'],
        'cheque_amount'=>['nullable','numeric','min:0'], 'credit_amount'=>['nullable','numeric','min:0'],
        'other_amount'=>['nullable','numeric','min:0'], 'note'=>['nullable','string','max:2000'],
    ]; }
}
