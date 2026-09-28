<?php
namespace Modules\EzyLaw\Entities;
class LawDocument extends LawModel {
 protected $table='law_documents'; protected $guarded=['id']; protected $casts=['confidential'=>'boolean'];
 public function versions(){return $this->hasMany(LawDocumentVersion::class,'document_id')->orderByDesc('version_no');}
    public function approvals(){return $this->hasMany(LawDocumentApproval::class,'document_id')->orderByDesc('id');}
    public function esignRequests(){return $this->hasMany(LawEsignRequest::class,'document_id')->orderByDesc('id');}
}
