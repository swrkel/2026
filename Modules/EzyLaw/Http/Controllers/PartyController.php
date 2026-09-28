<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawMatter,LawMatterParty};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class PartyController extends Controller
{
    public function index(LawMatter $matter){return view('ezylaw::parties.index',['matter'=>$matter,'parties'=>$matter->parties()->orderBy('party_type')->orderBy('name')->get()]);}
    public function store(Request $r,LawMatter $matter){$d=$this->data($r);LawMatterParty::create($d+['business_id'=>EzyLawTenantGuard::businessId(),'matter_id'=>$matter->id]);return back()->with('success','Matter party added.');}
    public function update(Request $r,LawMatter $matter,LawMatterParty $party){abort_unless((int)$party->matter_id===(int)$matter->id,404);$party->update($this->data($r));return back()->with('success','Matter party updated.');}
    public function destroy(LawMatter $matter,LawMatterParty $party){abort_unless((int)$party->matter_id===(int)$matter->id,404);$party->delete();return back()->with('success','Matter party removed.');}
    private function data(Request $r):array{return $r->validate(['party_type'=>'required|in:client,opponent,witness,counsel,expert,court_officer,other','name'=>'required|string|max:191','role'=>'nullable|string|max:100','phone'=>'nullable|string|max:50','email'=>'nullable|email|max:191','address'=>'nullable|string']);}
}
