<?php
namespace Modules\PumperDashboardNew\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class OperatorDocumentRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array { return [
        'title'=>['nullable','string','max:191'], 'category'=>['required','string','max:80'],
        'visibility'=>['required',Rule::in(['operator','management'])],
        'document'=>['required','file','max:'.(int)config('pumperdashboardnew.documents.maximum_kilobytes',10240),'mimes:'.implode(',',config('pumperdashboardnew.documents.allowed_mimes',[]))],
    ]; }
}
