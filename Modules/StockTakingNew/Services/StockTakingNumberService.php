<?php
namespace Modules\StockTakingNew\Services;
use Illuminate\Support\Facades\DB;
class StockTakingNumberService
{
    public function next(int $businessId, ?int $locationId = null): string
    {
        return DB::transaction(function () use ($businessId, $locationId) {
            $key = 'session:' . ($locationId ?: 0) . ':' . now()->format('Y');
            $row = DB::table('stk_number_sequences')->where('business_id',$businessId)->where('sequence_key',$key)->lockForUpdate()->first();
            $next = $row ? ((int) $row->last_number + 1) : 1;
            if ($row) DB::table('stk_number_sequences')->where('id',$row->id)->update(['last_number'=>$next,'updated_at'=>now()]);
            else DB::table('stk_number_sequences')->insert(['business_id'=>$businessId,'sequence_key'=>$key,'last_number'=>$next,'created_at'=>now(),'updated_at'=>now()]);
            $prefix = $this->setting($businessId,'number_prefix',config('stocktakingnew.defaults.number_prefix','STK-'));
            return $prefix . now()->format('Y') . '-' . str_pad((string)$next, 6, '0', STR_PAD_LEFT);
        });
    }
    private function setting(int $businessId,string $key,$default)
    {
        $row=DB::table('stk_settings')->where('business_id',$businessId)->where('setting_key',$key)->first();
        return $row ? ($row->setting_value ?? $default) : $default;
    }
}
