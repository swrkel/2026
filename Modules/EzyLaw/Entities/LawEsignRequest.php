<?php
namespace Modules\EzyLaw\Entities;
class LawEsignRequest extends LawModel {
    protected $table='law_esign_requests'; protected $guarded=['id']; protected $hidden=['token_hash'];
    protected $casts=['requested_at'=>'datetime','expires_at'=>'datetime','viewed_at'=>'datetime','signed_at'=>'datetime','declined_at'=>'datetime'];
    public function document(){return $this->belongsTo(LawDocument::class,'document_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
}
