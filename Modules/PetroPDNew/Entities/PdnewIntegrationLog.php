<?php

namespace Modules\PetroPDNew\Entities;

class PdnewIntegrationLog extends PdnewBaseModel
{
    protected $table = 'pdnew_integration_logs';
    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
}
