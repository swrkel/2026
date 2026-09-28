<?php
namespace Modules\EzyLaw\Entities;
class LawPortalMessage extends LawModel {
    protected $table='law_portal_messages'; protected $guarded=['id']; protected $casts=['sent_at'=>'datetime'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
