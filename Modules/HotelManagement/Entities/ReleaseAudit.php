<?php
namespace Modules\HotelManagement\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReleaseAudit extends Model
{

    protected $table = 'hm_release_audits';
    protected $guarded = ['id'];
    protected $casts = ['payload' => 'array'];
}
