<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class CreateDeliveryDispatchRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.delivery.use')??false;}
    public function rules():array{return ['delivery_zone_id'=>'nullable|integer','delivery_address'=>'required|string|max:2000','instructions'=>'nullable|string|max:2000'];}
}
