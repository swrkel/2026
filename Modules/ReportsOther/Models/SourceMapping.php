<?php

namespace Modules\ReportsOther\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceMapping extends Model
{
    public $timestamps = false;
    protected $table = 'reo_source_mappings';

    protected $fillable = ['source_id', 'item_type', 'item_id', 'item_name_snapshot', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class, 'source_id');
    }
}
