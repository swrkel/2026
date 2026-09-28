<?php
namespace Modules\EzyLaw\Entities;
class LawAppointment extends LawModel
{
    protected $table='law_appointments';
    protected $guarded=['id'];
    protected $casts=['start_at'=>'datetime','end_at'=>'datetime'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
