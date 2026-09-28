<?php

namespace Modules\DistributionNew\Utils;

use Illuminate\Support\Facades\DB;

class DisnewNumberUtil
{
    public static function next(string $type, string $prefix, int $businessId): string
    {
        $row = DB::table('disnew_number_sequences')->where('business_id', $businessId)->where('type', $type)->lockForUpdate()->first();
        if (!$row) {
            DB::table('disnew_number_sequences')->insert(['business_id' => $businessId, 'type' => $type, 'prefix' => $prefix, 'next_number' => 2, 'padding' => 6, 'created_at' => now(), 'updated_at' => now()]);
            return $prefix . str_pad('1', 6, '0', STR_PAD_LEFT);
        }
        DB::table('disnew_number_sequences')->where('id', $row->id)->update(['next_number' => ((int) $row->next_number) + 1, 'updated_at' => now()]);
        return ($row->prefix ?: $prefix) . str_pad((string) $row->next_number, (int) ($row->padding ?: 6), '0', STR_PAD_LEFT);
    }
}
