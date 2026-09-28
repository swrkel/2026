<?php
namespace Modules\Ran\Entities;
class DocumentTemplate extends RanModel {
 protected $table='ran_document_templates';
 protected $casts=['style_options'=>'array','is_default'=>'boolean','is_active'=>'boolean'];
    public function sections(){return $this->hasMany(DocumentTemplateSection::class,'template_id')->orderBy('sort_order');}
}
