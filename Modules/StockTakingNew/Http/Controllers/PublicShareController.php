<?php
namespace Modules\StockTakingNew\Http\Controllers;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Modules\StockTakingNew\Entities\StockTakeShareLink;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Services\DocumentService;
class PublicShareController extends Controller
{
 private function resolve(string $token): array
 {
  abort_unless(Schema::hasTable('stk_share_links'),404);$link=StockTakeShareLink::where('token_hash',hash('sha256',$token))->firstOrFail();
  abort_if($link->revoked_at || $link->expires_at->isPast(),410,'This secure document link has expired.');$session=StockTakeSession::findOrFail($link->session_id);
  $link->increment('access_count');$link->update(['last_accessed_at'=>now()]);return [$link,$session];
 }
 public function show(string $token,DocumentService $d){[$link,$session]=$this->resolve($token);return view('stocktakingnew::shares.public',$d->viewData($session,$link->document_type)+['link'=>$link,'token'=>$token]);}
 public function download(string $token,DocumentService $d){[$link,$session]=$this->resolve($token);abort_unless($link->download_allowed,403);return $d->pdf($session,$link->document_type,true);}
}
