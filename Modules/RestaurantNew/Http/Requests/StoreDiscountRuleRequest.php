<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreDiscountRuleRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.discounts.manage')??false;}
    public function rules():array{return ['location_id'=>'nullable|integer','rule_code'=>'required|string|max:50','name'=>'required|string|max:140','discount_type'=>'required|in:percentage,fixed','discount_value'=>'required|numeric|min:0','maximum_discount'=>'nullable|numeric|min:0','minimum_order'=>'nullable|numeric|min:0','starts_on'=>'nullable|date','ends_on'=>'nullable|date|after_or_equal:starts_on','starts_at'=>'nullable|date_format:H:i','ends_at'=>'nullable|date_format:H:i','days'=>'nullable|array','days.*'=>'integer|min:0|max:6','order_type'=>'nullable|in:dine_in,takeaway,delivery','requires_manager'=>'nullable|boolean'];}
}
