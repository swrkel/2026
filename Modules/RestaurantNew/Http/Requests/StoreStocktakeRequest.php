<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreStocktakeRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.stock.stocktake')??false;}
    public function rules():array{return ['location_id'=>'required|integer','stocktake_date'=>'required|date','notes'=>'nullable|string|max:2000'];}
}
