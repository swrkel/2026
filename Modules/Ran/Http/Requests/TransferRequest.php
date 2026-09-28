<?php
namespace Modules\Ran\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class TransferRequest extends FormRequest {
 public function authorize():bool{return auth()->check();}
 public function rules():array{return ['transfer_date'=>'required|date','from_location_id'=>'required|integer','from_store_id'=>'required|integer','to_location_id'=>'required|integer','to_store_id'=>'required|integer','notes'=>'nullable|string|max:2000','lines'=>'required|array|min:1','lines.*.stock_lot_id'=>'required|integer','lines.*.quantity'=>'required|numeric|min:0.0001','lines.*.weight'=>'required|numeric|min:0'];}
 public function withValidator($validator):void{$validator->after(function($validator){if((string)$this->from_location_id===(string)$this->to_location_id&&(string)$this->from_store_id===(string)$this->to_store_id)$validator->errors()->add('to_store_id','Destination location and store must differ from the source.');foreach((array)$this->input('lines',[]) as $i=>$line)if((float)($line['quantity']??0)<=0&&(float)($line['weight']??0)<=0)$validator->errors()->add("lines.$i.quantity",'Quantity or weight must be greater than zero.');});}
}
