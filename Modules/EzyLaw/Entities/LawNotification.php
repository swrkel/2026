<?php
namespace Modules\EzyLaw\Entities;
class LawNotification extends LawModel {
    protected $table='law_notifications'; protected $guarded=['id'];
    protected $casts=['scheduled_at'=>'datetime','sent_at'=>'datetime','read_at'=>'datetime'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
}
