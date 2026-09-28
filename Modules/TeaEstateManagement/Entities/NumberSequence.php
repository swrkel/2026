<?php
namespace Modules\TeaEstateManagement\Entities;
class NumberSequence extends BaseTeaModel
{
    protected $table = 'tea_number_sequences';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
