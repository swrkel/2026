<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class HubIdentityService
{
    public function createHubDealer(array $data): array
    {
        return app(HubDatabaseManager::class)->central(function() use($data) {
            return DB::transaction(function() use($data) {
                $now=now();
                $hubCode=$this->uniqueHubCode();
                $dealerId=DB::table('dlr_hub_dealers')->insertGetId([
                    'hub_code'=>$hubCode,'name'=>$data['name'],'mobile'=>$data['mobile']??null,'email'=>$data['email']??null,
                    'address'=>$data['address']??null,'status'=>'active','notes'=>$data['notes']??null,'created_at'=>$now,'updated_at'=>$now,
                ]);
                $outletId=DB::table('dlr_hub_outlets')->insertGetId([
                    'hub_dealer_id'=>$dealerId,'outlet_code'=>'MAIN','name'=>$data['outlet_name']??'Main Outlet','is_default'=>1,'is_active'=>1,
                    'created_at'=>$now,'updated_at'=>$now,
                ]);
                $loginCode=$this->uniqueLoginCode($dealerId);
                $tempPassword=$data['password']??('Dlr@'.random_int(100000,999999));
                $userId=DB::table('dlr_hub_users')->insertGetId([
                    'hub_dealer_id'=>$dealerId,'name'=>$data['admin_name']??$data['name'],'login_code'=>$loginCode,
                    'mobile'=>$data['admin_mobile']??($data['mobile']??null),'email'=>$data['admin_email']??($data['email']??null),
                    'password'=>Hash::make($tempPassword),'role_name'=>'Dealer Admin','permissions_json'=>json_encode(['*']),'is_hub_admin'=>1,
                    'is_active'=>1,'must_change_password'=>1,'created_at'=>$now,'updated_at'=>$now,
                ]);
                DB::table('dlr_hub_user_outlets')->insert(['hub_user_id'=>$userId,'hub_outlet_id'=>$outletId,'created_at'=>$now,'updated_at'=>$now]);
                return ['hub_dealer_id'=>$dealerId,'hub_code'=>$hubCode,'hub_user_id'=>$userId,'login_code'=>$loginCode,'temporary_password'=>$tempPassword,'hub_outlet_id'=>$outletId];
            });
        });
    }

    public function uniqueHubCode(): string
    {
        do {$code='DLRH'.str_pad((string)random_int(1,999999),6,'0',STR_PAD_LEFT);} while(DB::table('dlr_hub_dealers')->where('hub_code',$code)->exists());
        return $code;
    }
    public function uniqueLoginCode(int $dealerId): string
    {
        do {$code=str_pad((string)random_int(0,9999),4,'0',STR_PAD_LEFT);} while(DB::table('dlr_hub_users')->where('hub_dealer_id',$dealerId)->where('login_code',$code)->exists());
        return $code;
    }
}
