<?php
namespace Modules\EzyLaw\Entities;
class LawClientAdvance extends LawModel {
    protected $table='law_client_advances'; protected $guarded=['id'];
    protected $casts=['received_on'=>'date','amount'=>'decimal:4','allocated_amount'=>'decimal:4','balance'=>'decimal:4'];
    public function client(){return $this->belongsTo(LawClient::class,'client_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function allocations(){return $this->hasMany(LawAdvanceAllocation::class,'advance_id');}
}
