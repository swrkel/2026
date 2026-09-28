<?php
namespace Modules\RestaurantNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreSupplierRequest extends FormRequest
{
    public function authorize():bool{return $this->user()?->can('restaurant_new.procurement.manage')??false;}
    public function rules():array{return ['location_id'=>'nullable|integer','supplier_code'=>'required|string|max:60','name'=>'required|string|max:180','contact_person'=>'nullable|string|max:160','phone'=>'nullable|string|max:60','email'=>'nullable|email|max:160','address'=>'nullable|string|max:2000','tax_no'=>'nullable|string|max:80','credit_limit'=>'nullable|numeric|min:0','credit_days'=>'nullable|integer|min:0|max:3650'];}
}
