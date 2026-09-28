<?php
namespace Modules\EzyLaw\Entities;
class LawTimeEntry extends LawModel{protected $table='law_time_entries';protected $guarded=['id'];protected $casts=['work_date'=>'date','rate'=>'decimal:4','amount'=>'decimal:4','billable'=>'boolean','invoiced'=>'boolean'];public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}}
