<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SharedCustomerAdapter
{
    public function list($businessId = null, $term = null)
    {
        $connection = DB::getDefaultConnection();
        $database = DB::connection($connection)->getDatabaseName();
        $schemaKey = 'autoservice:contacts-schema:' . sha1($connection . '|' . $database);

        $schema = Cache::remember($schemaKey, now()->addMinutes(10), static function () use ($connection) {
            $builder = Schema::connection($connection);

            return [
                'exists' => $builder->hasTable('contacts'),
                'has_business_id' => $builder->hasTable('contacts') && $builder->hasColumn('contacts', 'business_id'),
            ];
        });

        if (!$schema['exists']) {
            return collect();
        }

        $q = DB::connection($connection)
            ->table('contacts')
            ->where(function ($query) {
                $query->whereNull('type')
                    ->orWhere('type', 'customer')
                    ->orWhere('type', 'both');
            });

        if ($businessId && $schema['has_business_id']) {
            $q->where('business_id', $businessId);
        }

        if ($term) {
            $q->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('mobile', 'like', "%{$term}%")
                    ->orWhere('contact_id', 'like', "%{$term}%");
            });
        }

        return $q->orderBy('name')->limit(100)->get(['id', 'name', 'mobile']);
    }
}
