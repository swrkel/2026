<?php
namespace Modules\Ran\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StocktakeRequest extends FormRequest {public function authorize():bool{return auth()->check();} public function rules():array{return ['stocktake_date'=>'required|date','location_id'=>'required|integer','store_id'=>'required|integer','notes'=>'nullable|string|max:2000','lines'=>'required|array|min:1','lines.*.stock_lot_id'=>'required|integer','lines.*.counted_quantity'=>'required|numeric|min:0','lines.*.counted_weight'=>'required|numeric|min:0'];}}
