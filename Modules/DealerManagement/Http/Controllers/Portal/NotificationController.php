<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller; use Illuminate\Support\Facades\DB; use Modules\DealerManagement\Services\DealerContext;
class NotificationController extends Controller { public function index(){ $u=app(DealerContext::class)->user(); $notifications=DB::table('dlr_notifications')->where('dealer_id',$u->dealer_id)->orderByDesc('id')->simplePaginate(50); return view('dealermanagement::notifications.index',compact('notifications')); } public function read(int $id){ $u=app(DealerContext::class)->user(); DB::table('dlr_notifications')->where('dealer_id',$u->dealer_id)->where('id',$id)->update(['is_read'=>1,'read_at'=>now(),'updated_at'=>now()]); return back(); } }
