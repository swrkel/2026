<?php
namespace Modules\StockTakingNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Http\Requests\ShareDocumentRequest;
use Modules\StockTakingNew\Services\SettingsService;
use Modules\StockTakingNew\Services\ShareService;
use Modules\StockTakingNew\Services\TenantScopeService;
class ShareController extends Controller
{
 public function create(StockTakeSession $session,Request $r,TenantScopeService $s,SettingsService $settings){$s->assertBusinessRecord($session,$s->businessId($r));$moduleSettings=$settings->all((int)$session->business_id);$dispatches=$session->shareLinks()->with('dispatches')->latest()->limit(20)->get();return view('stocktakingnew::shares.create',compact('session','moduleSettings','dispatches'));}
 public function store(StockTakeSession $session,ShareDocumentRequest $r,TenantScopeService $s,ShareService $service){$s->assertBusinessRecord($session,$s->businessId($r));$data=$r->validated();$data['download_allowed']=$r->boolean('download_allowed');try{$result=$service->send($session,$data);return back()->with('status','Document sharing request completed.')->with('share_result',$result);}catch(\Throwable $e){return back()->withErrors($e->getMessage());}}
}
