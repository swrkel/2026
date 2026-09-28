<?php
namespace Modules\Ran\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class CustomerPaymentRequest extends FormRequest{
 public function authorize():bool{return auth()->check();}
 public function rules():array{return ['payment_date'=>'required|date','sale_id'=>'nullable|integer','customer_contact_id'=>'nullable|integer','finance_account_id'=>'required|integer','payment_method'=>'required|string|max:40','amount'=>'required|numeric|min:0.0001','reference_no'=>'nullable|string|max:100','notes'=>'nullable|string|max:2000'];}
 public function withValidator($validator):void{$validator->after(function($validator){if(!$this->sale_id&&!$this->customer_contact_id)$validator->errors()->add('customer_contact_id','Select a customer or an outstanding invoice.');});}
}
