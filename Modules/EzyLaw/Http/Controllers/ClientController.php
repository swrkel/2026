<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller; use Modules\EzyLaw\Entities\LawClient; use Modules\EzyLaw\Services\ClientService;
class ClientController extends Controller
{
 public function index(Request $r){$q=LawClient::query(); if($r->filled('q')){$x=$r->q;$q->where(fn($z)=>$z->where('name','like','%'.$x.'%')->orWhere('client_no','like','%'.$x.'%')->orWhere('mobile','like','%'.$x.'%')->orWhere('email','like','%'.$x.'%'));} return view('ezylaw::clients.index',['clients'=>$q->orderByDesc('id')->paginate(25)->appends($r->query())]);}
 public function create(){return view('ezylaw::clients.form',['client'=>new LawClient]);}
 public function store(Request $r,ClientService $s){$c=$s->store($this->validateData($r)); return redirect()->route('ezylaw.clients.show',$c)->with('success','Client saved successfully.');}
 public function show(LawClient $client){$client->load(['matters','invoices']); return view('ezylaw::clients.show',compact('client'));}
 public function edit(LawClient $client){return view('ezylaw::clients.form',compact('client'));}
 public function update(Request $r,LawClient $client,ClientService $s){$s->update($client,$this->validateData($r,$client->id)); return redirect()->route('ezylaw.clients.show',$client)->with('success','Client updated successfully.');}
 public function destroy(LawClient $client){$client->delete(); return redirect()->route('ezylaw.clients.index')->with('success','Client deleted.');}
 private function validateData(Request $r,$id=null):array{return $r->validate(['client_no'=>'nullable|string|max:50','client_type'=>'required|in:individual,company','name'=>'required|string|max:191','company_name'=>'nullable|string|max:191','nic_passport'=>'nullable|string|max:100','registration_no'=>'nullable|string|max:100','email'=>'nullable|email|max:191','phone'=>'nullable|string|max:50','mobile'=>'nullable|string|max:50','address_line_1'=>'nullable|string|max:191','address_line_2'=>'nullable|string|max:191','city'=>'nullable|string|max:100','country'=>'nullable|string|max:100','tax_no'=>'nullable|string|max:100','contact_person'=>'nullable|string|max:191','status'=>'required|in:active,inactive','notes'=>'nullable|string']);}
}
