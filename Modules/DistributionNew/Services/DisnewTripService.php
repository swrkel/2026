<?php
namespace Modules\DistributionNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewTrip;
use Modules\DistributionNew\Models\DisnewTripLine;

class DisnewTripService
{
    public function createTrip(array $header, array $lines): DisnewTrip
    {
        return DB::transaction(function () use ($header, $lines) {
            $trip = DisnewTrip::create($header + ['status' => $header['status'] ?? 'planned']);
            foreach ($lines as $index => $line) {
                DisnewTripLine::create($line + [
                    'business_id' => $trip->business_id,
                    'trip_id' => $trip->id,
                    'sequence_no' => $line['sequence_no'] ?? ($index + 1),
                    'status' => $line['status'] ?? 'pending',
                ]);
            }
            return $trip;
        });
    }

    public function validateCapacity(array $payload): bool
    {
        return ((float)($payload['loaded_weight'] ?? 0) <= (float)($payload['capacity_weight'] ?? 0) || empty($payload['capacity_weight']))
            && ((float)($payload['loaded_volume'] ?? 0) <= (float)($payload['capacity_volume'] ?? 0) || empty($payload['capacity_volume']));
    }
}
