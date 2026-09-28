<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreStockTransferRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.stock.transfer')??false;}
    public function rules():array{return ['from_location_id'=>'required|integer','to_location_id'=>'required|integer|different:from_location_id','transfer_date'=>'required|date','notes'=>'nullable|string|max:2000','lines'=>'required|array|min:1','lines.*.ingredient_id'=>'required|integer|distinct','lines.*.quantity'=>'required|numeric|min:0.0001','lines.*.notes'=>'nullable|string|max:500'];}
}
