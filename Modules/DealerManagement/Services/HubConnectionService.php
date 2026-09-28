<?php
namespace Modules\DealerManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HubConnectionService
{
    public function registerCurrentDistributor(int $businessId, ?string $name=null): object
    {
        $database=(string)config('database.connections.mysql.database');
        $name=$name ?: (string)(DB::table('business')->where('id',$businessId)->value('name') ?: $database);
        return app(HubDatabaseManager::class)->central(function() use($database,$businessId,$name) {
            $row=DB::table('dlr_hub_distributors')->where('database_name',$database)->where('business_id',$businessId)->first();
            if($row){DB::table('dlr_hub_distributors')->where('id',$row->id)->update(['name'=>$name,'status'=>'active','last_seen_at'=>now(),'updated_at'=>now()]); return DB::table('dlr_hub_distributors')->where('id',$row->id)->first();}
            do {$code='DIST'.str_pad((string)random_int(1,999999),6,'0',STR_PAD_LEFT);} while(DB::table('dlr_hub_distributors')->where('distributor_code',$code)->exists());
            $id=DB::table('dlr_hub_distributors')->insertGetId(['distributor_code'=>$code,'name'=>$name,'database_name'=>$database,'business_id'=>$businessId,'status'=>'active','last_seen_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
            return DB::table('dlr_hub_distributors')->where('id',$id)->first();
        });
    }

    public function invite(int $hubDealerId, int $businessId, int $localDealerId): array
    {
        $dist=$this->registerCurrentDistributor($businessId);
        return app(HubDatabaseManager::class)->central(function() use($hubDealerId,$dist,$localDealerId) {
            $token=strtoupper(Str::random(8));
            $id=DB::table('dlr_hub_connections')->updateOrInsert(
                ['hub_dealer_id'=>$hubDealerId,'distributor_id'=>$dist->id],
                ['local_dealer_id'=>$localDealerId,'status'=>'pending','invitation_code'=>$token,'connection_source'=>'distributor_invite','invited_at'=>now(),'updated_at'=>now(),'created_at'=>now()]
            );
            $row=DB::table('dlr_hub_connections')->where('hub_dealer_id',$hubDealerId)->where('distributor_id',$dist->id)->first();
            return ['connection'=>$row,'distributor'=>$dist,'invitation_code'=>$row->invitation_code];
        });
    }

    public function approveByCode(int $hubDealerId, string $code, int $hubUserId): object
    {
        return app(HubDatabaseManager::class)->central(function() use($hubDealerId,$code,$hubUserId) {
            $row=DB::table('dlr_hub_connections')->where('hub_dealer_id',$hubDealerId)->where('invitation_code',strtoupper(trim($code)))->first();
            abort_unless($row,422,'Invalid invitation code.');
            DB::table('dlr_hub_connections')->where('id',$row->id)->update(['status'=>'active','approved_at'=>now(),'approved_by_hub_user_id'=>$hubUserId,'updated_at'=>now()]);
            return DB::table('dlr_hub_connections')->where('id',$row->id)->first();
        });
    }

    public function requestByDistributorCode(int $hubDealerId, string $code): object
    {
        return app(HubDatabaseManager::class)->central(function() use($hubDealerId,$code) {
            $dist=DB::table('dlr_hub_distributors')->where('distributor_code',strtoupper(trim($code)))->where('status','active')->first();
            abort_unless($dist,422,'Distributor code was not found.');
            DB::table('dlr_hub_connections')->updateOrInsert(['hub_dealer_id'=>$hubDealerId,'distributor_id'=>$dist->id],[
                'status'=>'requested','connection_source'=>'dealer_request','invited_at'=>now(),'updated_at'=>now(),'created_at'=>now()
            ]);
            return DB::table('dlr_hub_connections')->where('hub_dealer_id',$hubDealerId)->where('distributor_id',$dist->id)->first();
        });
    }
}
