<?php
namespace Modules\EzyLaw\Entities;
class LawDocumentVersion extends LawModel {
    protected $table='law_document_versions'; protected $guarded=['id'];
    public function document(){return $this->belongsTo(LawDocument::class,'document_id');}
}
