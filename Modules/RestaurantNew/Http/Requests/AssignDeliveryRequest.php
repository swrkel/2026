<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class AssignDeliveryRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.delivery.manage')??false;}
    public function rules():array{return ['driver_user_id'=>'nullable|integer'];}
}
