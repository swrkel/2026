<?php

namespace Modules\PetroPDNew\Entities;

class PdnewPrintLog extends PdnewBaseModel
{
    protected $table = 'pdnew_print_logs';
    protected $casts = [
        'printed_at' => 'datetime',
        'metadata' => 'array',
    ];
}
