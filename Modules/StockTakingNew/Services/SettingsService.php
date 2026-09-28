<?php
namespace Modules\StockTakingNew\Services;
use Illuminate\Support\Facades\Crypt;
use Modules\StockTakingNew\Entities\StockTakeSetting;
class SettingsService
{
    public function all(int $businessId): array
    {
        $defaults=[
            'number_prefix'=>config('stocktakingnew.defaults.number_prefix','STK-'),'quantity_decimals'=>config('stocktakingnew.defaults.quantity_decimals',4),
            'amount_decimals'=>config('stocktakingnew.defaults.amount_decimals',4),'default_count_mode'=>config('stocktakingnew.defaults.count_mode','blind'),
            'require_approval'=>config('stocktakingnew.defaults.require_approval',true),'require_recount_for_variance'=>config('stocktakingnew.defaults.require_recount_for_variance',true),
            'post_to_shared_inventory'=>config('stocktakingnew.defaults.post_to_shared_inventory',true),'share_link_expiry_hours'=>config('stocktakingnew.defaults.share_link_expiry_hours',168),
            'sms_endpoint'=>config('stocktakingnew.communication.sms_endpoint'),'sms_token'=>config('stocktakingnew.communication.sms_token'),'sms_sender_id'=>config('stocktakingnew.communication.sms_sender_id'),
            'whatsapp_endpoint'=>config('stocktakingnew.communication.whatsapp_endpoint'),'whatsapp_token'=>config('stocktakingnew.communication.whatsapp_token'),'whatsapp_phone_number_id'=>config('stocktakingnew.communication.whatsapp_phone_number_id'),
        ];
        foreach(StockTakeSetting::where('business_id',$businessId)->get() as $row){
            $value=$row->setting_value;
            if($row->is_encrypted && $value){try{$value=Crypt::decryptString($value);}catch(\Throwable $e){$value='';}}
            $defaults[$row->setting_key]=$row->value_json ?? $value;
        }
        return $defaults;
    }
    public function setMany(int $businessId,array $data): void
    {
        $encrypted=['sms_token','whatsapp_token'];
        foreach($data as $key=>$value){
            $isEncrypted=in_array($key,$encrypted,true) && filled($value);
            StockTakeSetting::updateOrCreate(['business_id'=>$businessId,'setting_key'=>$key],[
                'setting_value'=>$isEncrypted?Crypt::encryptString((string)$value):(is_bool($value)?($value?'1':'0'):(string)($value??'')),
                'value_json'=>is_array($value)?$value:null,'is_encrypted'=>$isEncrypted,'updated_by'=>auth()->id(),
            ]);
        }
    }
    public function bool(array $settings,string $key,bool $default=false): bool { return filter_var($settings[$key]??$default,FILTER_VALIDATE_BOOLEAN); }
}
