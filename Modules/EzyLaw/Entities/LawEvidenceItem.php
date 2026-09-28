<?php
namespace Modules\EzyLaw\Entities;
class LawEvidenceItem extends LawModel {
    protected $table='law_evidence_items'; protected $guarded=['id'];
    protected $casts=['received_on'=>'date','confidential'=>'boolean'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function document(){return $this->belongsTo(LawDocument::class,'document_id');}
}
