<?php
namespace Modules\DealerManagement\Http\Controllers\Portal;
use App\Http\Controllers\Controller; use Illuminate\Support\Facades\DB; use Modules\DealerManagement\Services\DealerContext;
class ReturnController extends Controller { public function index(){ $u=app(DealerContext::class)->user(); $dealer=DB::table('dlr_dealers')->where('id',$u->dealer_id)->first(); $returns=collect(); if($dealer && $dealer->customer_id && \Illuminate\Support\Facades\Schema::hasTable('disnew_returns')) $returns=DB::table('disnew_returns')->where('business_id',$u->business_id)->where('customer_id',$dealer->customer_id)->orderByDesc('id')->paginate(30); return view('dealermanagement::returns.index',compact('returns')); } }
