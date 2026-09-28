<?php
namespace Modules\Ran\Http\Requests;use Illuminate\Foundation\Http\FormRequest;
class SupplierPaymentRequest extends FormRequest{public function authorize():bool{return auth()->check();}public function rules():array{return ['payment_date'=>'required|date','purchase_id'=>'nullable|integer','supplier_contact_id'=>'required|integer','finance_account_id'=>'required|integer','amount'=>'required|numeric|min:0.0001','payment_method'=>'required|string|max:40','reference_no'=>'nullable|string|max:100','notes'=>'nullable|string|max:2000'];}}
