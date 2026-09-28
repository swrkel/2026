<?php
namespace Modules\EzyLaw\Entities;
class LawMediationSession extends LawModel {
    protected $table='law_mediation_sessions'; protected $guarded=['id'];
    protected $casts=['session_at'=>'datetime','next_session_at'=>'datetime'];
    public function settlement(){return $this->belongsTo(LawSettlement::class,'settlement_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
