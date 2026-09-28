<?php
namespace Modules\TeaEstateManagement\Entities;
class BatchInput extends BaseTeaModel
{
    protected $table = 'tea_batch_inputs';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
