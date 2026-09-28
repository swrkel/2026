<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawEvidenceItem,LawMatter,LawDocument}; use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class EvidenceController extends Controller {
 public function index(Request $r){$matterId=$r->input('matter_id');$q=LawEvidenceItem::with(['matter','document'])->orderByDesc('id');if($matterId)$q->where('matter_id',$matterId);return view('ezylaw::evidence.index',['items'=>$q->paginate(30),'matters'=>LawMatter::orderBy('matter_no')->get(),'documents'=>LawDocument::orderBy('title')->get(),'matterId'=>$matterId]);}
 public function store(Request $r){$d=$r->validate(['matter_id'=>'required|integer','evidence_no'=>'required|string|max:80','title'=>'required|string|max:191','evidence_type'=>'required|string|max:80','description'=>'nullable|string','received_on'=>'nullable|date','custody_location'=>'nullable|string|max:191','document_id'=>'nullable|integer','status'=>'required|string|max:30','confidential'=>'nullable|boolean']);LawEvidenceItem::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'confidential'=>$r->boolean('confidential'),'created_by'=>auth()->id()]);return back()->with('success','Evidence / exhibit registered.');}
 public function destroy(LawEvidenceItem $evidence){$evidence->delete();return back()->with('success','Evidence item removed.');}
}
