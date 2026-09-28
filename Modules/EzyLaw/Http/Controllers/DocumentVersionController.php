<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Illuminate\Support\Facades\Storage;
use Modules\EzyLaw\Entities\{LawDocument,LawDocumentVersion}; use Modules\EzyLaw\Services\DocumentVersionService;
class DocumentVersionController extends Controller {
 public function store(Request $r,LawDocument $document,DocumentVersionService $s){$d=$r->validate(['file'=>'required|file|max:20480','change_note'=>'nullable|string|max:1000']);$s->add($document,$r->file('file'),$d['change_note']??null);return back()->with('success','New document version uploaded.');}
 public function download(LawDocumentVersion $version){return Storage::disk(config('ezylaw.document_disk','public'))->download($version->file_path,$version->file_name);}
}
