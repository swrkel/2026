<?php
namespace Modules\EzyLaw\Entities;
class LawCourtFiling extends LawModel {
    protected $table='law_court_filings'; protected $guarded=['id'];
    protected $casts=['filed_on'=>'date','response_due_at'=>'datetime','fee_amount'=>'decimal:4'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function court(){return $this->belongsTo(LawCourt::class,'court_id');}
}
