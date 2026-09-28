<?php
namespace Modules\EzyLaw\Entities;
class LawConflictCheck extends LawModel
{
    protected $table='law_conflict_checks';
    protected $guarded=['id'];
    protected $casts=['checked_at'=>'datetime'];
    public function matches(){return $this->hasMany(LawConflictMatch::class,'conflict_check_id');}
}
