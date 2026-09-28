<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    protected $table = 'reo_sources';

    protected $fillable = [
        'business_id', 'location_id', 'store_id', 'scope_key', 'source_name',
        'created_by', 'created_by_name',
    ];

    public function mappings(): HasMany
    {
        return $this->hasMany(SourceMapping::class, 'source_id');
    }
}
