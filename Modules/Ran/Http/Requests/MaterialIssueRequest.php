<?php
namespace Modules\Ran\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class MaterialIssueRequest extends FormRequest {
 public function authorize():bool{return auth()->check();}
 public function rules():array{return ['issue_date'=>'required|date','notes'=>'nullable|string|max:2000','lines'=>'required|array|min:1','lines.*.stock_lot_id'=>'required|integer','lines.*.quantity'=>'required|numeric|min:0.0001','lines.*.net_weight'=>'required|numeric|min:0'];}
 public function withValidator($validator):void{$validator->after(function($validator){foreach((array)$this->input('lines',[]) as $i=>$line)if((float)($line['quantity']??0)<=0&&(float)($line['net_weight']??0)<=0)$validator->errors()->add("lines.$i.quantity",'Quantity or net weight must be greater than zero.');});}
}
