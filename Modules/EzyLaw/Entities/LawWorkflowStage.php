<?php
namespace Modules\EzyLaw\Entities;
class LawWorkflowStage extends LawModel {
    protected $table='law_workflow_stages'; protected $guarded=['id']; protected $casts=['required'=>'boolean','active'=>'boolean'];
    public function template(){return $this->belongsTo(LawWorkflowTemplate::class,'workflow_template_id');}
}
