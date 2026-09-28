<?php
namespace Modules\Ran\Http\Requests;use Illuminate\Foundation\Http\FormRequest;
class SaleReturnRequest extends FormRequest{public function authorize():bool{return auth()->check();}public function rules():array{return ['return_date'=>'required|date','sale_id'=>'required|integer','reason'=>'required|string|max:2000','lines'=>'required|array|min:1','lines.*.sale_line_id'=>'required|integer','lines.*.quantity'=>'required|numeric|min:0.0001','lines.*.weight'=>'nullable|numeric|min:0','lines.*.amount'=>'required|numeric|min:0','lines.*.condition'=>'required|in:good,repair,scrap'];}}
