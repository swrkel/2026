<?php
namespace Modules\PumperDashboardNew\Http\Controllers\Operator;
use Illuminate\Support\Facades\Storage;
use Modules\PumperDashboardNew\Entities\PoneOperatorDocument;
use Modules\PumperDashboardNew\Entities\PoneOperatorNote;
use Modules\PumperDashboardNew\Http\Controllers\Controller;
use Modules\PumperDashboardNew\Http\Requests\OperatorDocumentRequest;
use Modules\PumperDashboardNew\Http\Requests\OperatorNoteRequest;
use Modules\PumperDashboardNew\Services\PoneContextService;
use Modules\PumperDashboardNew\Services\PoneDocumentService;
class DocumentController extends Controller
{
    public function __construct(private PoneContextService $context,private PoneDocumentService $documents){}
    public function index(){ $profile=$this->context->profile();$notes=PoneOperatorNote::query()->where('business_id',$profile->business_id)->where('operator_profile_id',$profile->id)->latest()->get();$documents=PoneOperatorDocument::query()->where('business_id',$profile->business_id)->where('operator_profile_id',$profile->id)->latest()->get();return view('pumperdashboardnew::operator.documents.index',compact('profile','notes','documents'));}
    public function addNote(OperatorNoteRequest $request){$this->documents->addNote($request->validated());return $this->ok(__('pumperdashboardnew::lang.note_saved'));}
    public function upload(OperatorDocumentRequest $request){$this->documents->upload($request->validated(),$request->file('document'));return $this->ok(__('pumperdashboardnew::lang.document_uploaded'));}
    public function download(int $document){$document=PoneOperatorDocument::query()->whereKey($document)->firstOrFail();$this->documents->guardDocument($document);abort_unless(Storage::disk($document->disk)->exists($document->path),404);return Storage::disk($document->disk)->download($document->path,$document->original_name);}
    public function destroy(int $document){$document=PoneOperatorDocument::query()->whereKey($document)->firstOrFail();$this->documents->deleteDocument($document);return $this->ok(__('pumperdashboardnew::lang.document_deleted'));}
    public function archive(int $note){$note=PoneOperatorNote::query()->whereKey($note)->firstOrFail();$this->documents->archiveNote($note);return $this->ok(__('pumperdashboardnew::lang.note_archived'));}
}
