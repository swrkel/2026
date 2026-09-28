<?php
namespace Modules\EzyLaw\Entities;
class LawPortalAuditLog extends LawModel {
    public $timestamps=false; protected $table='law_portal_audit_logs'; protected $guarded=['id']; protected $casts=['created_at'=>'datetime'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function access(){return $this->belongsTo(LawPortalAccess::class,'portal_access_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
