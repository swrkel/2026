<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ReservationStatusRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.reservations.manage')??false;}
    public function rules():array{return ['status'=>'required|in:confirmed,seated,completed,cancelled,no_show','reason'=>'nullable|string|max:1000'];}
}
