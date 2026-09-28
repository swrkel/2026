<?php
namespace Modules\DistributionNew\Models;
use Illuminate\Database\Eloquent\Model;
class DisnewPortalAuditLog extends Model
{
    public $timestamps = false;
    protected $table = 'disnew_portal_audit_logs';
    protected $fillable = ['business_id','portal_type','actor_id','action','reference_type','reference_id','ip_address','user_agent','created_at'];
}
