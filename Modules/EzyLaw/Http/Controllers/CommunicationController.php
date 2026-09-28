<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawCommunication,LawClient,LawMatter};
use Modules\EzyLaw\Services\CommunicationService;
class CommunicationController extends Controller
{
    public function index(Request $r){$q=LawCommunication::with(['client','matter']);if($r->filled('channel'))$q->where('channel',$r->channel);return view('ezylaw::communications.index',['communications'=>$q->orderByDesc('id')->paginate(30),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderByDesc('id')->get()]);}
    public function store(Request $r,CommunicationService $s){$d=$r->validate(['client_id'=>'nullable|integer','matter_id'=>'nullable|integer','channel'=>'required|in:email,sms,whatsapp,phone,letter,meeting,other','direction'=>'required|in:outbound,inbound','recipient'=>'nullable|string|max:191','sender'=>'nullable|string|max:191','subject'=>'nullable|string|max:191','body'=>'nullable|string','status'=>'required|in:logged,draft,queued,sent,failed','sent_at'=>'nullable|date','external_reference'=>'nullable|string|max:150']);$s->log($d);return back()->with('success','Communication recorded.');}
}
