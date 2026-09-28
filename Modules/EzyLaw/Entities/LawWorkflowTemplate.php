<?php
namespace Modules\EzyLaw\Entities;
class LawWorkflowTemplate extends LawModel {
    protected $table='law_workflow_templates'; protected $guarded=['id']; protected $casts=['active'=>'boolean'];
    public function stages(){return $this->hasMany(LawWorkflowStage::class,'workflow_template_id')->orderBy('sequence_no');}
    public function practiceArea(){return $this->belongsTo(LawPracticeArea::class,'practice_area_id');}
}
