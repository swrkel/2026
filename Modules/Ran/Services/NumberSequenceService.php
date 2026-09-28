<?php

namespace Modules\Ran\Services;

use Illuminate\Support\Facades\DB;
use Modules\Ran\Entities\NumberSequence;
use Modules\Ran\Support\RanContext;

class NumberSequenceService
{
    public function next(string $documentType, ?int $locationId = null): string
    {
        return DB::transaction(function () use ($documentType, $locationId): string {
            $businessId = RanContext::businessId();
            $locationId = $locationId ?: RanContext::locationId();
            $sequence = NumberSequence::withoutGlobalScopes()
                ->where('business_id', $businessId)
                ->where('document_type', $documentType)
                ->where(function ($query) use ($locationId) {
                    $locationId ? $query->where('location_id', $locationId) : $query->whereNull('location_id');
                })
                ->lockForUpdate()->first();

            if (! $sequence) {
                $sequence = NumberSequence::withoutGlobalScopes()->create([
                    'business_id' => $businessId,
                    'location_id' => $locationId,
                    'document_type' => $documentType,
                    'prefix' => strtoupper(substr(preg_replace('/[^a-z0-9]/i', '', $documentType), 0, 4)).'-',
                    'next_number' => 1,
                    'padding' => 5,
                    'reset_period' => 'never',
                    'created_by' => RanContext::userId(),
                ]);
            }

            $number = (string) ($sequence->prefix ?? '').str_pad((string) $sequence->next_number, (int) $sequence->padding, '0', STR_PAD_LEFT);
            $sequence->increment('next_number');
            return $number;
        }, 3);
    }
}
