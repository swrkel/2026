<?php
namespace Modules\PumperDashboardNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class OperatorNoteRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return ['note_type'=>['required',Rule::in(['general','incident','hand_over','settlement'])], 'title'=>['required','string','max:191'], 'body'=>['required','string','max:10000']]; }
}
