<?php
namespace Modules\EzyLaw\Entities;
class LawPortalAccess extends LawModel {
    protected $table='law_portal_access'; protected $guarded=['id'];
    protected $hidden=['token_hash']; protected $casts=['last_login_at'=>'datetime','expires_at'=>'datetime'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
}
