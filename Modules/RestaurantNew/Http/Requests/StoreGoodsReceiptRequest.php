<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreGoodsReceiptRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.procurement.manage')??false;}
    public function rules():array{return ['location_id'=>'required|integer','supplier_id'=>'nullable|integer','supplier_invoice_no'=>'nullable|string|max:100','received_date'=>'required|date','notes'=>'nullable|string|max:2000','lines'=>'required|array|min:1','lines.*.ingredient_id'=>'required|integer','lines.*.quantity'=>'required|numeric|min:0.0001','lines.*.unit_cost'=>'required|numeric|min:0','lines.*.discount_amount'=>'nullable|numeric|min:0','lines.*.tax_amount'=>'nullable|numeric|min:0','lines.*.batch_no'=>'nullable|string|max:100','lines.*.expiry_date'=>'nullable|date','lines.*.notes'=>'nullable|string|max:500'];}
}
