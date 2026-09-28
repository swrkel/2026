<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawPortalAccess,LawPortalMessage,LawClient,LawMatter}; use Modules\EzyLaw\Services\PortalService;
class PortalController extends Controller {
 public function index(){return view('ezylaw::portal.index',['access'=>LawPortalAccess::with('client')->orderByDesc('id')->paginate(20),'messages'=>LawPortalMessage::with(['client','matter'])->orderByDesc('id')->limit(50)->get(),'clients'=>LawClient::where('status','active')->orderBy('name')->get(),'matters'=>LawMatter::whereIn('status',['open','pending'])->orderBy('matter_no')->get()]);}
 public function access(Request $r,PortalService $s){$d=$r->validate(['client_id'=>'required|integer','email'=>'required|email|max:191','contact_id'=>'nullable|integer','expires_at'=>'nullable|date']);$result=$s->createAccess($d);return back()->with('success','Client portal access created. One-time token: '.$result['token']);}
 public function revoke(LawPortalAccess $access){$access->update(['status'=>'revoked']);return back()->with('success','Portal access revoked.');}
 public function message(Request $r,PortalService $s){$d=$r->validate(['client_id'=>'required|integer','matter_id'=>'nullable|integer','direction'=>'required|in:inbound,outbound','subject'=>'nullable|string|max:191','body'=>'required|string']);$s->logMessage($d);return back()->with('success','Portal message logged.');}
}
