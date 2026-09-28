<?php
namespace Modules\LeadsNew\Requests;
use Illuminate\Foundation\Http\FormRequest;
class LeadNewOpportunityRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return []; } }
