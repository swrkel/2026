<?php
namespace Modules\EzyLaw\Entities;
class LawTask extends LawModel
{
    protected $table='law_tasks';
    protected $guarded=['id'];
    protected $casts=['due_at'=>'datetime','completed_at'=>'datetime'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
