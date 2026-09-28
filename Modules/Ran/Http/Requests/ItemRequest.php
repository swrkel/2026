<?php
namespace Modules\Ran\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ItemRequest extends FormRequest {
 public function authorize():bool{return auth()->check();}
 public function rules():array{return ['sku'=>'required|string|max:60','barcode'=>'nullable|string|max:80','name'=>'required|string|max:180','item_type'=>'required|in:raw_material,finished,gemstone,packing,service','category'=>'nullable|string|max:100','design_id'=>'nullable|integer','metal_id'=>'nullable|integer','purity_id'=>'nullable|integer','stock_unit'=>'required|string|max:20','standard_gross_weight'=>'nullable|numeric|min:0','standard_net_weight'=>'nullable|numeric|min:0','standard_stone_weight'=>'nullable|numeric|min:0','making_charge'=>'nullable|numeric|min:0','making_charge_type'=>'required|in:fixed,per_gram,percentage','default_sale_price'=>'nullable|numeric|min:0','minimum_sale_price'=>'nullable|numeric|min:0','description'=>'nullable|string|max:3000','serialized'=>'boolean','is_active'=>'boolean','components'=>'nullable|array','components.*.component_type'=>'required_with:components|in:metal,gemstone,labour,other','components.*.metal_id'=>'nullable|integer','components.*.purity_id'=>'nullable|integer','components.*.gemstone_id'=>'nullable|integer','components.*.quantity'=>'nullable|numeric|min:0','components.*.weight'=>'nullable|numeric|min:0','components.*.unit_cost'=>'nullable|numeric|min:0'];}
 protected function prepareForValidation():void{$this->merge(['serialized'=>$this->boolean('serialized'),'is_active'=>$this->boolean('is_active')]);}
}
