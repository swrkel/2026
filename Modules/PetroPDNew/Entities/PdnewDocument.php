<?php

namespace Modules\PetroPDNew\Entities;

use Illuminate\Database\Eloquent\SoftDeletes;

class PdnewDocument extends PdnewBaseModel
{
    use SoftDeletes;

    protected $table = 'pdnew_documents';

    protected $casts = [
        'metadata' => 'array',
    ];
}
