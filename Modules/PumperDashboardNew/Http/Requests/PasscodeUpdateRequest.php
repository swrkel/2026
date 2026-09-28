<?php
namespace Modules\PumperDashboardNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PasscodeUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['current_passcode'=>['required','string','max:20'], 'new_passcode'=>['required','string','min:1','max:20','confirmed']]; }
}
