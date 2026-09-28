<?php
namespace Modules\SimpleAudit\Models;
class Setting extends SimpleAuditModel
{
    protected $table = 'sau_settings';
    public $timestamps = true;
    protected $casts = ['value_json' => 'array'];
}
