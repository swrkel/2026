<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreOrderRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.orders.create')??false;}
    public function rules():array{return [
        'location_id'=>'nullable|integer','table_id'=>'nullable|integer','reservation_id'=>'nullable|integer','delivery_zone_id'=>'nullable|integer',
        'order_type'=>'required|in:dine_in,takeaway,delivery','customer_name'=>'nullable|string|max:160','customer_phone'=>'nullable|string|max:60','customer_email'=>'nullable|email|max:160',
        'delivery_address'=>'nullable|string|max:2000','guest_count'=>'nullable|integer|min:1|max:100','notes'=>'nullable|string|max:2000','send_to_kitchen'=>'nullable|boolean',
        'items'=>'required|array|min:1','items.*.menu_item_id'=>'required|integer','items.*.quantity'=>'required|numeric|min:0.0001','items.*.modifier_ids'=>'nullable|array','items.*.modifier_ids.*'=>'integer','items.*.notes'=>'nullable|string|max:500','items.*.discount_amount'=>'nullable|numeric|min:0'
    ];}
}
