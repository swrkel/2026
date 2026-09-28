<?php
namespace Modules\EggManagement\Models;

class IntegrationOutbox extends EggModel
{
    protected $table = 'egg_integration_outbox';
    protected $casts = ['payload'=>'array','processed_at'=>'datetime'];
}
