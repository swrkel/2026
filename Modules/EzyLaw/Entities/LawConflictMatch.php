<?php
namespace Modules\EzyLaw\Entities;
class LawConflictMatch extends LawModel
{
    protected $table='law_conflict_matches';
    protected $guarded=['id'];
    protected $casts=['match_score'=>'decimal:4'];
    public function check(){return $this->belongsTo(LawConflictCheck::class,'conflict_check_id');}
}
