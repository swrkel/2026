<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreReservationRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.reservations.manage')??false;}
    public function rules():array{return ['location_id'=>'nullable|integer','table_id'=>'nullable|integer','customer_name'=>'required|string|max:160','customer_phone'=>'required|string|max:60','customer_email'=>'nullable|email|max:160','guest_count'=>'required|integer|min:1|max:100','reserved_at'=>'required|date','duration_minutes'=>'nullable|integer|min:15|max:720','source'=>'required|in:phone,walk_in,web,other','deposit_amount'=>'nullable|numeric|min:0','notes'=>'nullable|string|max:2000'];}
}
