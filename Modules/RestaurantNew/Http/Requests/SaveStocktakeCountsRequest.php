<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SaveStocktakeCountsRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.stock.stocktake')??false;}
    public function rules():array{return ['counts'=>'required|array','counts.*'=>'required|numeric|min:0'];}
}
