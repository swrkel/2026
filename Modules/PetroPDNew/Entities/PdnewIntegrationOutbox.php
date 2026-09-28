<?php

namespace Modules\PetroPDNew\Entities;

class PdnewIntegrationOutbox extends PdnewBaseModel
{
    protected $table = 'pdnew_integration_outbox';
    protected $casts = [
        'payload' => 'array',
        'available_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
