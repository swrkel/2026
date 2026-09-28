<?php
namespace Modules\AutoService\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AutoServiceApprovalRequest extends Model
{
    use SoftDeletes;
    protected $table = 'auto_service_approval_requests';
    protected $guarded = [];

    public function job(){ return $this->belongsTo(AutoServiceJob::class, 'job_id'); }
}
