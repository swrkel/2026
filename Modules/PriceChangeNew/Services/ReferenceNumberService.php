<?php

namespace Modules\PriceChangeNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PriceChangeNew\Entities\NumberSequence;

class ReferenceNumberService
{
    public function next(int $businessId): string
    {
        return DB::transaction(function () use ($businessId): string {
            $sequence = NumberSequence::query()
                ->where('business_id', $businessId)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                NumberSequence::query()->create([
                    'business_id' => $businessId,
                    'prefix' => 'PCN',
                    'next_number' => 1,
                    'padding' => 6,
                ]);

                $sequence = NumberSequence::query()
                    ->where('business_id', $businessId)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $number = (int) $sequence->next_number;
            $reference = sprintf(
                '%s-%s-%s',
                trim((string) $sequence->prefix) ?: 'PCN',
                now()->format('Ym'),
                str_pad((string) $number, max(1, (int) $sequence->padding), '0', STR_PAD_LEFT)
            );

            $sequence->next_number = $number + 1;
            $sequence->save();

            return $reference;
        }, 3);
    }
}
