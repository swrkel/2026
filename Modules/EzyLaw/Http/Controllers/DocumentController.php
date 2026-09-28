<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Illuminate\Support\Facades\Storage; use Modules\EzyLaw\Entities\{LawDocument,LawMatter,LawClient}; use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class DocumentController extends Controller
{
 public function index(){return view('ezylaw::documents.index',['documents'=>LawDocument::with('versions')->orderByDesc('id')->paginate(25),'matters'=>LawMatter::orderBy('matter_no')->get(),'clients'=>LawClient::orderBy('name')->get()]);}
 public function store(Request $r){$d=$r->validate(['matter_id'=>'nullable|integer','client_id'=>'nullable|integer','category'=>'nullable|string|max:100','title'=>'required|string|max:191','file'=>'required|file|max:20480','confidential'=>'nullable|boolean']);$file=$r->file('file');$path=$file->store(config('ezylaw.document_directory','ezylaw/documents').'/'.EzyLawTenantGuard::businessId(),config('ezylaw.document_disk','public'));LawDocument::create(['business_id'=>EzyLawTenantGuard::businessId(),'matter_id'=>$d['matter_id']??null,'client_id'=>$d['client_id']??null,'category'=>$d['category']??null,'title'=>$d['title'],'file_name'=>$file->getClientOriginalName(),'file_path'=>$path,'mime_type'=>$file->getMimeType(),'file_size'=>$file->getSize(),'version'=>1,'confidential'=>$r->boolean('confidential'),'uploaded_by'=>auth()->id()]);return back()->with('success','Document uploaded.');}
 public function download(LawDocument $document){return Storage::disk(config('ezylaw.document_disk','public'))->download($document->file_path,$document->file_name);}
}
