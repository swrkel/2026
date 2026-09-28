<?php

namespace Modules\PetroPDNew\Entities;

class PdnewOperatorMapping extends PdnewBaseModel
{
    protected $table = 'pdnew_operator_mappings';
    protected $casts = [
        'settings' => 'array',
        'last_synced_at' => 'datetime',
    ];
}
