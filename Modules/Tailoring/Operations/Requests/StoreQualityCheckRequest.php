<?php
namespace Modules\Tailoring\Operations\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreQualityCheckRequest extends FormRequest{public function authorize():bool{return true;} public function rules():array{return ['job_card_id'=>'required|integer','checklist_result'=>'required|string|max:50','checked_at'=>'nullable|date','remarks'=>'nullable|string'];}}
