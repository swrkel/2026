<?php

namespace Modules\PetroPDNew\Entities;

class PdnewSourceSnapshot extends PdnewBaseModel
{
    protected $table = 'pdnew_source_snapshots';
    protected $casts = [
        'snapshot' => 'array',
        'captured_at' => 'datetime',
    ];

    public function sourceImport()
    {
        return $this->belongsTo(PdnewSourceImport::class, 'source_import_id');
    }
}
