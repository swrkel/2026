<?php

namespace Modules\ReportsOther\Services;

use Illuminate\Support\Facades\DB;
use Modules\ReportsOther\Models\NumberSequence;
use Modules\ReportsOther\Support\CurrentScope;
use RuntimeException;

class SequenceService
{
    public function __construct(private readonly CurrentScope $scope)
    {
    }

    public function current(string $documentKey): ?NumberSequence
    {
        return NumberSequence::query()
            ->where('scope_key', $this->scope->key())
            ->where('document_key', $documentKey)
            ->first();
    }


    public function peek(string $documentKey): ?string
    {
        $sequence = $this->current($documentKey);
        if (!$sequence) {
            return null;
        }

        return (string) ($sequence->prefix ?? '').(int) $sequence->next_number;
    }

    public function configure(string $documentKey, ?string $prefix, int $startingNumber): NumberSequence
    {
        if ($startingNumber < 1) {
            throw new RuntimeException('Starting number must be at least 1.');
        }

        return NumberSequence::query()->updateOrCreate(
            ['scope_key' => $this->scope->key(), 'document_key' => $documentKey],
            [
                'business_id' => $this->scope->businessId(),
                'location_id' => $this->scope->locationId(),
                'store_id' => $this->scope->storeId(),
                'prefix' => ($prefix = trim((string) $prefix)) !== '' ? $prefix : null,
                'next_number' => $startingNumber,
                'created_by' => $this->scope->userId(),
                'updated_by' => $this->scope->userId(),
            ]
        );
    }

    /**
     * Returns the receipt number that must be used now and atomically advances
     * the sequence. The configured starting number is therefore the first number.
     */
    public function consume(string $documentKey): string
    {
        return DB::transaction(function () use ($documentKey) {
            /** @var NumberSequence|null $sequence */
            $sequence = NumberSequence::query()
                ->where('scope_key', $this->scope->key())
                ->where('document_key', $documentKey)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                throw new RuntimeException('Receipt numbering is not configured.');
            }

            $number = (int) $sequence->next_number;
            $sequence->next_number = $number + 1;
            $sequence->updated_by = $this->scope->userId();
            $sequence->save();

            return (string) ($sequence->prefix ?? '').$number;
        }, 3);
    }
}
