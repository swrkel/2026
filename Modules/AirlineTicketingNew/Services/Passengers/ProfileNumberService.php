<?php

namespace Modules\AirlineTicketingNew\Services\Passengers;

use Illuminate\Support\Facades\DB;

class ProfileNumberService
{
    public function next(int $businessId, string $type, string $prefix): string
    {
        return DB::transaction(function () use ($businessId, $type, $prefix): string {
            $row = DB::table('atn_sequences')
                ->where('business_id', $businessId)
                ->whereNull('business_location_id')
                ->whereNull('store_id')
                ->where('document_type', $type)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                DB::table('atn_sequences')->insert([
                    'business_id' => $businessId,
                    'document_type' => $type,
                    'prefix' => $prefix,
                    'next_number' => 2,
                    'padding' => 6,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return $prefix . '000001';
            }

            DB::table('atn_sequences')->where('id', $row->id)->update([
                'next_number' => $row->next_number + 1,
                'updated_at' => now(),
            ]);

            return (string) $row->prefix
                . str_pad((string) $row->next_number, (int) $row->padding, '0', STR_PAD_LEFT);
        });
    }
}
