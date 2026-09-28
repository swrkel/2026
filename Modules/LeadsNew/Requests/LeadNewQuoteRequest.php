<?php
namespace Modules\LeadsNew\Requests;
use Illuminate\Foundation\Http\FormRequest;
class LeadNewQuoteRequest extends FormRequest { public function authorize(): bool { return true; } public function rules(): array { return []; } }
