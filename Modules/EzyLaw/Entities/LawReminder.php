<?php
namespace Modules\EzyLaw\Entities;
class LawReminder extends LawModel
{
    protected $table='law_reminders';
    protected $guarded=['id'];
    protected $casts=['remind_at'=>'datetime','sent_at'=>'datetime'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
