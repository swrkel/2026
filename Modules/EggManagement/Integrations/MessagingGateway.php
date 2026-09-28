<?php
namespace Modules\EggManagement\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class MessagingGateway
{
    public function email($to, $subject, $body)
    {
        Mail::raw($body, function($m) use ($to,$subject){ $m->to($to)->subject($subject); });
        return true;
    }

    public function sms($to, $message)
    {
        $endpoint = config('egg.sharing.sms_endpoint');
        if (!$endpoint) return ['sent'=>false,'reason'=>'SMS endpoint is not configured'];
        $request = Http::asJson();
        if ($token = config('egg.sharing.sms_token')) $request = $request->withToken($token);
        $r = $request->post($endpoint, ['to'=>$to,'message'=>$message]);
        return ['sent'=>$r->successful(),'status'=>$r->status()];
    }

    public function whatsappUrl($to, $message)
    {
        $digits = preg_replace('/\D+/', '', (string)$to);
        if (strpos($digits,'0')===0) $digits = config('egg.sharing.whatsapp_phone_prefix','94').substr($digits,1);
        return 'https://wa.me/'.$digits.'?text='.rawurlencode($message);
    }
}
