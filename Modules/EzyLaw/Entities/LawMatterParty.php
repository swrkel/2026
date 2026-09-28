<?php
namespace Modules\EzyLaw\Entities;
class LawMatterParty extends LawModel
{
    protected $table='law_matter_parties';
    protected $guarded=['id'];
    public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}
}
