<?php
namespace Modules\Tailoring\Operations\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreMaterialIssueRequest extends FormRequest{public function authorize():bool{return true;} public function rules():array{return ['job_card_id'=>'required|integer','material_type'=>'required|string|max:100','quantity'=>'required|numeric|min:0','issue_date'=>'required|date'];}}
