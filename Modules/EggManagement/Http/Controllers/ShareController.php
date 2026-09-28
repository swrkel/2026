<?php
namespace Modules\EggManagement\Http\Controllers;
use Illuminate\Http\Request;use Modules\EggManagement\Services\ShareService;use Modules\EggManagement\Services\ReportDataService;use Modules\EggManagement\Integrations\MessagingGateway;
class ShareController extends BaseController
{
    public function create(Request $r,ShareService $share,MessagingGateway $msg){$d=$r->validate(['resource_type'=>'required|in:production,stock,sales,purchases,movements,wastage,audit','resource_id'=>'nullable|integer','channel'=>'required|in:link,email,sms,whatsapp','to'=>'nullable|string|max:255','parameters'=>'nullable|array','parameters.from'=>'nullable|date','parameters.to'=>'nullable|date','parameters.location_id'=>'nullable','parameters.store_id'=>'nullable']);$url=$share->create($d['resource_type'],$d['resource_id']??null,'html',$d['parameters']??[]);$text='Egg Management report: '.$url;if($d['channel']==='email'){$msg->email($d['to'],'Egg Management Report',$text);}elseif($d['channel']==='sms'){$msg->sms($d['to'],$text);}elseif($d['channel']==='whatsapp'){return response()->json(['url'=>$url,'whatsapp_url'=>$msg->whatsappUrl($d['to'],$text)]);}return response()->json(['url'=>$url]);}
    public function publicView($token,ShareService $share,ReportDataService $reports){$link=$share->resolve($token);$data=$reports->forShare($link->business_id,$link->resource_type,$link->parameters?:[]);return view('egg::share.public',['link'=>$link,'report'=>$data]);}
}
