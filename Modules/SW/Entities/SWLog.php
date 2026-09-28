<?php

namespace Modules\SW\Entities;

class SWLog extends SWModel
{
    protected $table = 'sw_logs';

    protected $casts = [
        'changes' => 'array',
        'after_closure' => 'boolean',
    ];

    public const DOC_SHIFT = 'shift';
    public const DOC_SETTLEMENT = 'settlement';
}
