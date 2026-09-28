<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSourceImport extends PdnewBaseModel
{
    protected $table = 'pdnew_source_imports';
    protected $casts = [
        'imported_at' => 'datetime',
        'verified_at' => 'datetime',
        'source_closed_at' => 'datetime',
        'source_totals' => 'array',
    ];

    public function settlement()
    {
        return $this->belongsTo(PdnewSettlement::class, 'settlement_id');
    }

    public function snapshots()
    {
        return $this->hasMany(PdnewSourceSnapshot::class, 'source_import_id');
    }
}
