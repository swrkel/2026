<?php
namespace Modules\EzyLaw\Entities;
class LawResearchItem extends LawModel {
    protected $table='law_research_items'; protected $guarded=['id'];
    protected $casts=['decision_date'=>'date','confidential'=>'boolean'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
    public function practiceArea(){return $this->belongsTo(LawPracticeArea::class,'practice_area_id');}
}
