<?php
namespace Modules\EzyLaw\Entities;
class LawDocumentApproval extends LawModel {
    protected $table='law_document_approvals'; protected $guarded=['id'];
    protected $casts=['requested_at'=>'datetime','responded_at'=>'datetime'];
    public function document(){return $this->belongsTo(LawDocument::class,'document_id');}
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
