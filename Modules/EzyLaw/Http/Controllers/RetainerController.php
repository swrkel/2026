<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawRetainer,LawClient,LawMatter};
use Modules\EzyLaw\Services\RetainerService;
class RetainerController extends Controller
{
    public function index(){return view('ezylaw::retainers.index',['retainers'=>LawRetainer::with(['client','matter'])->orderByDesc('id')->paginate(25),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderByDesc('id')->get()]);}
    public function store(Request $r,RetainerService $s){$d=$r->validate(['retainer_no'=>'nullable|string|max:50','client_id'=>'required|integer','matter_id'=>'nullable|integer','agreement_date'=>'required|date','start_date'=>'nullable|date','end_date'=>'nullable|date','retainer_type'=>'required|in:general,specific,security,costs,other','agreed_amount'=>'required|numeric|min:0','replenishment_threshold'=>'nullable|numeric|min:0','status'=>'required|in:active,suspended,closed','notes'=>'nullable|string']);$s->create($d);return back()->with('success','Retainer agreement saved.');}
    public function close(LawRetainer $retainer,RetainerService $s){$s->close($retainer);return back()->with('success','Retainer closed.');}
}
