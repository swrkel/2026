<?php
namespace Modules\EzyLaw\Entities;
class LawNote extends LawModel { protected $table = 'law_notes'; protected $guarded = ['id']; protected $casts = ['confidential'=>'boolean']; }
