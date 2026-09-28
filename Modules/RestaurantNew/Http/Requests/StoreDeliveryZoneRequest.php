<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreDeliveryZoneRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.delivery.manage')??false;}
    public function rules():array{return ['location_id'=>'nullable|integer','zone_code'=>'required|string|max:50','name'=>'required|string|max:120','minimum_order'=>'nullable|numeric|min:0','delivery_fee'=>'nullable|numeric|min:0','estimated_minutes'=>'nullable|integer|min:1|max:1440'];}
}
