<?php
namespace Modules\EzyLaw\Entities;
class LawMatterStageHistory extends LawModel {
    protected $table='law_matter_stage_history'; protected $guarded=['id'];
    protected $casts=['started_at'=>'datetime','due_at'=>'datetime','completed_at'=>'datetime'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function stage(){return $this->belongsTo(LawWorkflowStage::class,'workflow_stage_id');}
}
