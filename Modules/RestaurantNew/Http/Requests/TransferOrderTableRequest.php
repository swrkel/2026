<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class TransferOrderTableRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.orders.edit')??false;}
    public function rules():array{return ['table_id'=>'required|integer','reason'=>'required|string|min:3|max:1000'];}
}
