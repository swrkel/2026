<?php
namespace Modules\AirlineTicketingNew\Services\Security;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class EncryptedSettingService
{
    public function put(int $businessId,string $key,mixed $value): void
    {
        DB::table('atn_encrypted_settings')->updateOrInsert(
            ['business_id'=>$businessId,'setting_key'=>$key],
            ['encrypted_value'=>Crypt::encryptString(json_encode($value)),'updated_at'=>now(),'created_at'=>now()]
        );
    }

    public function get(int $businessId,string $key,mixed $default=null): mixed
    {
        $value=DB::table('atn_encrypted_settings')
            ->where('business_id',$businessId)
            ->where('setting_key',$key)
            ->value('encrypted_value');

        return $value ? json_decode(Crypt::decryptString($value),true) : $default;
    }
}
