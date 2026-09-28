<?php
namespace Modules\Audit\Models;
use Illuminate\Database\Eloquent\Model;
class AuditResolution extends Model
{
    protected $table = 'audit_resolutions';
    protected $guarded = [];
    protected $casts = ['before_data' => 'array', 'after_data' => 'array'];
}
