<?php

namespace Modules\PetroDirectNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\PetroDirectNew\Entities\PdirectnewNumberSequence;
use Modules\PetroDirectNew\Support\BusinessContext;

class NumberSequenceService
{
    public function __construct(private BusinessContext $context) {}

    public function next(string $type, ?int $locationId = null, string $defaultPrefix = ''): string
    {
        return DB::transaction(function () use ($type, $locationId, $defaultPrefix) {
            $businessId = $this->context->requireBusiness();
            $sequence = PdirectnewNumberSequence::query()
                ->where('business_id', $businessId)
                ->where('location_id', (int) ($locationId ?: 0))
                ->where('sequence_type', $type)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                $sequence = PdirectnewNumberSequence::create([
                    'business_id' => $businessId,
                    'location_id' => (int) ($locationId ?: 0),
                    'sequence_type' => $type,
                    'prefix' => $defaultPrefix,
                    'next_number' => 1,
                    'padding' => 5,
                ]);
            }

            $number = (int) $sequence->next_number;
            $sequence->next_number = $number + 1;
            $sequence->save();

            return (string) $sequence->prefix . str_pad((string) $number, (int) $sequence->padding, '0', STR_PAD_LEFT);
        });
    }
}
