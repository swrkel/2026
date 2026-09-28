<?php
namespace Modules\EzyLaw\Entities;
class LawHearing extends LawModel
{
    protected $table='law_hearings'; protected $guarded=['id']; protected $casts=['hearing_at'=>'datetime','next_hearing_at'=>'datetime'];
    public function matter(){ return $this->belongsTo(LawMatter::class,'matter_id'); }
    public function court(){ return $this->belongsTo(LawCourt::class,'court_id'); }
}
