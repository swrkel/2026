<?php
namespace Modules\EzyLaw\Entities;
class LawCommunication extends LawModel
{
    protected $table='law_communications';
    protected $guarded=['id'];
    protected $casts=['sent_at'=>'datetime'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
