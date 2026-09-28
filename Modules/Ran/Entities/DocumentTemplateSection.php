<?php
namespace Modules\Ran\Entities;
class DocumentTemplateSection extends RanModel {
 protected $table='ran_document_template_sections';
 protected $casts=['enabled_by_default'=>'boolean','allow_print'=>'boolean','allow_sms'=>'boolean','allow_email'=>'boolean','allow_whatsapp'=>'boolean','settings'=>'array'];
    public function template(){return $this->belongsTo(DocumentTemplate::class,'template_id');}
}
