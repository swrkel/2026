<?php
namespace Modules\EzyLaw\Entities;
class LawExpense extends LawModel{protected $table='law_expenses';protected $guarded=['id'];protected $casts=['expense_date'=>'date','amount'=>'decimal:4','billable'=>'boolean','invoiced'=>'boolean'];public function matter(){return $this->belongsTo(LawMatter::class,'matter_id');}}
