<?php
namespace Modules\EzyLaw\Entities;
class LawSettlement extends LawModel {
    protected $table='law_settlements'; protected $guarded=['id'];
    protected $casts=['offered_on'=>'date','accepted_on'=>'date','completed_on'=>'date','offer_amount'=>'decimal:4','agreed_amount'=>'decimal:4','confidential'=>'boolean'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function sessions(){return $this->hasMany(LawMediationSession::class,'settlement_id')->orderBy('session_at');}
}
