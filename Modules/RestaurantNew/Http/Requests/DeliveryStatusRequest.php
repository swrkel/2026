<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class DeliveryStatusRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.delivery.use')??false;}
    public function rules():array{return ['status'=>'required|in:waiting,assigned,dispatched,delivered,failed,cancelled','cash_collected'=>'nullable|numeric|min:0'];}
}
