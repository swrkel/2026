<?php
namespace Modules\EzyLaw\Entities;
class LawDeadline extends LawModel {
    protected $table='law_deadlines'; protected $guarded=['id'];
    protected $casts=['basis_date'=>'date','due_at'=>'datetime','completed_at'=>'datetime'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
}
