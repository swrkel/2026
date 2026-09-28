<?php
namespace Modules\LeadsNew\Requests;
use Illuminate\Foundation\Http\FormRequest;
class LeadNewConvertRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return []; } }
