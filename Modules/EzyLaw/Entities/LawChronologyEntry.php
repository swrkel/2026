<?php
namespace Modules\EzyLaw\Entities;
class LawChronologyEntry extends LawModel
{
    protected $table='law_chronology_entries';
    protected $guarded=['id'];
    protected $casts=['event_at'=>'datetime','is_key_event'=>'boolean'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
