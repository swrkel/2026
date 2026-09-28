<?php
namespace Modules\PetroPDNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class WorkflowRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array { return ['note'=>'nullable|string|max:5000']; }
}
