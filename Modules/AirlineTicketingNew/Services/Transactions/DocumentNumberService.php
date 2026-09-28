<?php

namespace Modules\AirlineTicketingNew\Services\Transactions;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(int $businessId, ?int $locationId, ?int $storeId, string $type, string $prefix): string
    {
        return DB::transaction(function () use ($businessId, $locationId, $storeId, $type, $prefix): string {
            $query = DB::table('atn_sequences')
                ->where('business_id', $businessId)
                ->where('document_type', $type);

            $locationId ? $query->where('business_location_id', $locationId) : $query->whereNull('business_location_id');
            $storeId ? $query->where('store_id', $storeId) : $query->whereNull('store_id');

            $row = $query->lockForUpdate()->first();

            if (!$row) {
                DB::table('atn_sequences')->insert([
                    'business_id' => $businessId,
                    'business_location_id' => $locationId,
                    'store_id' => $storeId,
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
