<?php
namespace Modules\RestaurantNew\Services;
use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\NumberSequence;
class NumberService
{
    public function next(int $businessId, string $key, string $prefix, int $pad = 6): string
    {
        return DB::transaction(function () use ($businessId, $key, $prefix, $pad) {
            $row = NumberSequence::withoutGlobalScopes()->where('business_id', $businessId)->where('sequence_key', $key)->lockForUpdate()->first();
            if (!$row) {
                DB::table('restnew_number_sequences')->insertOrIgnore([
                    'business_id'=>$businessId,
                    'sequence_key'=>$key,
                    'last_number'=>0,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                $row = NumberSequence::withoutGlobalScopes()->where('business_id', $businessId)->where('sequence_key', $key)->lockForUpdate()->firstOrFail();
            }
            $row->last_number = (int) $row->last_number + 1;
            $row->save();
            return $prefix . str_pad((string) $row->last_number, $pad, '0', STR_PAD_LEFT);
        }, 3);
    }
}
