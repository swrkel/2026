<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreWastageRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.stock.wastage')??false;}
    public function rules():array{return ['location_id'=>'required|integer','wastage_date'=>'required|date','reason_code'=>'required|in:expired,spoiled,damaged,overproduction,staff_meal,other','notes'=>'nullable|string|max:2000','lines'=>'required|array|min:1','lines.*.ingredient_id'=>'required|integer','lines.*.quantity'=>'required|numeric|min:0.0001','lines.*.batch_no'=>'nullable|string|max:100','lines.*.notes'=>'nullable|string|max:500'];}
}
