<?php
namespace Modules\StockTakingNew\Services;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\StockTakingNew\Entities\StockTakeSession;
use Modules\StockTakingNew\Entities\StockTakeShareDispatch;
use Modules\StockTakingNew\Entities\StockTakeShareLink;
class ShareService
{
    public function __construct(private SettingsService $settings,private AuditService $audit){}
    public function createLink(StockTakeSession $session,string $documentType,int $expiryHours,bool $downloadAllowed=true): array
    {
        $token=Str::random(64); $link=StockTakeShareLink::create(['business_id'=>$session->business_id,'session_id'=>$session->id,'document_type'=>$documentType,'token_hash'=>hash('sha256',$token),
            'expires_at'=>now()->addHours($expiryHours),'download_allowed'=>$downloadAllowed,'created_by'=>auth()->id()]);
        return [$link,route('stock-taking-new.public.shared.show',['token'=>$token])];
    }
    public function send(StockTakeSession $session,array $data): array
    {
        $settings=$this->settings->all((int)$session->business_id); [$link,$url]=$this->createLink($session,$data['document_type'],(int)($data['expiry_hours']??$settings['share_link_expiry_hours']??168),!empty($data['download_allowed']));
        $message=trim((string)($data['message']??'')); if($message==='')$message="Stock Taking document {$session->stock_take_no}: {$url}"; elseif(!str_contains($message,$url))$message.="\n".$url;
        $dispatch=StockTakeShareDispatch::create(['business_id'=>$session->business_id,'session_id'=>$session->id,'share_link_id'=>$link->id,'channel'=>$data['channel'],'recipient'=>$data['recipient'],'recipient_name'=>$data['recipient_name']??null,'message'=>$message,'status'=>'pending','created_by'=>auth()->id()]);
        try{$result=match($data['channel']){'email'=>$this->email($data['recipient'],$session,$message),'sms'=>$this->sms($settings,$data['recipient'],$message),'whatsapp'=>$this->whatsapp($settings,$data['recipient'],$message),default=>throw new \RuntimeException('Unsupported channel')};
            $dispatch->update(['status'=>$result['status']??'sent','response'=>$result,'sent_at'=>now()]);
        }catch(\Throwable $e){$dispatch->update(['status'=>'failed','response'=>['error'=>$e->getMessage()],'failed_at'=>now()]); throw $e;}
        $this->audit->log($session->business_id,$session->id,'document_shared','share_dispatch',$dispatch->id,[],['channel'=>$data['channel'],'recipient'=>$data['recipient']]);
        return ['url'=>$url,'dispatch'=>$dispatch->refresh()];
    }
    private function email(string $recipient,StockTakeSession $session,string $message): array
    {
        Mail::raw($message,function($mail) use($recipient,$session){$mail->to($recipient)->subject('Stock Taking '.$session->stock_take_no);}); return ['status'=>'sent'];
    }
    private function sms(array $settings,string $recipient,string $message): array
    {
        $endpoint=trim((string)($settings['sms_endpoint']??'')); if($endpoint==='') return ['status'=>'link_ready','manual_url'=>'sms:'.preg_replace('/[^0-9+]/','',$recipient).'?body='.rawurlencode($message)];
        $request=Http::acceptJson()->timeout(30); if(!empty($settings['sms_token']))$request=$request->withToken((string)$settings['sms_token']);
        $response=$request->post($endpoint,['to'=>$recipient,'message'=>$message,'sender_id'=>$settings['sms_sender_id']??null]);
        if(!$response->successful())throw new \RuntimeException('SMS gateway error: '.$response->status().' '.$response->body()); return ['status'=>'sent','gateway'=>$response->json()?:$response->body()];
    }
    private function whatsapp(array $settings,string $recipient,string $message): array
    {
        $endpoint=trim((string)($settings['whatsapp_endpoint']??'')); $phoneId=trim((string)($settings['whatsapp_phone_number_id']??''));
        if($endpoint==='' && $phoneId==='') return ['status'=>'link_ready','manual_url'=>'https://wa.me/'.preg_replace('/\D+/','',$recipient).'?text='.rawurlencode($message)];
        if($endpoint==='')$endpoint='https://graph.facebook.com/v20.0/'.$phoneId.'/messages';
        $request=Http::acceptJson()->timeout(30); if(!empty($settings['whatsapp_token']))$request=$request->withToken((string)$settings['whatsapp_token']);
        $response=$request->post($endpoint,['messaging_product'=>'whatsapp','to'=>preg_replace('/\D+/','',$recipient),'type'=>'text','text'=>['body'=>$message,'preview_url'=>true]]);
        if(!$response->successful())throw new \RuntimeException('WhatsApp gateway error: '.$response->status().' '.$response->body()); return ['status'=>'sent','gateway'=>$response->json()?:$response->body()];
    }
}
