<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ApplyDiscountRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.discounts.apply')??false;}
    public function rules():array{return ['discount_rule_id'=>'required|integer','reason'=>'nullable|string|max:500'];}
}
