<?php

namespace Modules\PetroPDNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PetroPDNew\Entities\PdnewNumberSequence;

class PdnewNumberSequenceService
{
    public function next(int $businessId, ?int $locationId, string $type, string $defaultPrefix): string
    {
        return DB::transaction(function () use ($businessId, $locationId, $type, $defaultPrefix): string {
            $scope = implode(':', [$businessId, $locationId ?: 0, $type]);
            $now = now();

            // Creating the first number in a scope must also be safe when two
            // requests arrive together. INSERT IGNORE lets the unique scope key
            // select one winner; both requests then serialize on the same row.
            DB::table('pdnew_number_sequences')->insertOrIgnore([
                'business_id' => $businessId,
                'location_id' => $locationId,
                'scope_key' => $scope,
                'sequence_type' => $type,
                'prefix' => $defaultPrefix,
                'next_number' => 1,
                'padding' => 6,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sequence = PdnewNumberSequence::query()
                ->where('scope_key', $scope)
                ->lockForUpdate()
                ->firstOrFail();

            $number = (int) $sequence->next_number;
            $sequence->update(['next_number' => $number + 1]);

            return (string) $sequence->prefix
                . str_pad((string) $number, (int) $sequence->padding, '0', STR_PAD_LEFT);
        }, 3);
    }
}
