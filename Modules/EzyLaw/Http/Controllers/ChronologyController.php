<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawMatter,LawChronologyEntry};
use Modules\EzyLaw\Services\ChronologyService;
class ChronologyController extends Controller
{
    public function index(LawMatter $matter){return view('ezylaw::chronology.index',['matter'=>$matter,'entries'=>$matter->chronology()->paginate(30)]);}
    public function store(Request $r,LawMatter $matter,ChronologyService $s){$d=$r->validate(['event_at'=>'required|date','event_type'=>'required|string|max:80','title'=>'required|string|max:191','description'=>'nullable|string','is_key_event'=>'nullable|boolean']);$d['is_key_event']=$r->boolean('is_key_event');$s->add($matter,$d);return back()->with('success','Chronology entry added.');}
    public function destroy(LawMatter $matter,LawChronologyEntry $entry){abort_unless((int)$entry->matter_id===(int)$matter->id,404);$entry->delete();return back()->with('success','Chronology entry removed.');}
}
