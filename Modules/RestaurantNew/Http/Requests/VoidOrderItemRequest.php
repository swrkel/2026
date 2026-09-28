<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class VoidOrderItemRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.orders.void_item')??false;}
    public function rules():array{return ['reason'=>'required|string|min:3|max:1000'];}
}
