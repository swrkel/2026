<?php
namespace Modules\Graphs\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Graphs\Services\GraphAnalyticsService;

class ReorderAlertMiddleware
{
    public function __construct(private GraphAnalyticsService $analytics) {}

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        try {
            if (!$request->user() || !$request->isMethod('get') || $request->ajax() || $request->expectsJson()) return $response;
            $key=(string)config('graphs.alert_session_key','graphs_reorder_alert_shown');
            if ($request->session()->get($key)) return $response;
            $businessId=(int)$request->session()->get('user.business_id',0); if(!$businessId) return $response;
            $alerts=$this->analytics->reorderAlerts($businessId); $request->session()->put($key,true);
            if(!$alerts || !method_exists($response,'getContent')) return $response;
            $ct=(string)$response->headers->get('Content-Type',''); if(stripos($ct,'text/html')===false) return $response;
            $html=(string)$response->getContent(); if(stripos($html,'</body>')===false) return $response;
            $items=''; foreach(array_slice($alerts,0,8) as $a){$items.='<li><strong>'.e($a['tank']).'</strong> - '.e($a['product']).': '.number_format($a['current'],3).' (Re-order '.number_format($a['reorder'],3).')</li>';}
            $more=count($alerts)>8?'<div style="margin-top:6px">+'.(count($alerts)-8).' more tank(s)</div>':'';
            $banner='<div id="graphs-reorder-login-alert" style="position:fixed;z-index:999999;right:18px;top:18px;max-width:520px;background:#fff8e5;border:1px solid #e3b341;border-left:5px solid #c88a00;border-radius:10px;box-shadow:0 8px 28px rgba(0,0,0,.18);padding:14px 16px;font:14px/1.45 Calibri,"Segoe UI",Arial,sans-serif;color:#463700"><button onclick="this.parentNode.remove()" style="float:right;border:0;background:transparent;font-size:20px;cursor:pointer">&times;</button><div style="font-weight:700;font-size:16px;margin-bottom:6px">Fuel Tank Re-order Alert</div><div>Current stock has reached or fallen below the re-order level:</div><ul style="margin:7px 0 0 18px;padding:0">'.$items.'</ul>'.$more.'<div style="margin-top:9px"><a href="'.e(url('/graphs')).'" style="font-weight:700;color:#775b00">Open Graphs</a></div></div>';
            $response->setContent(str_ireplace('</body>',$banner.'</body>',$html));
        } catch(\Throwable $e) { /* analytics must never break login/navigation */ }
        return $response;
    }
}
