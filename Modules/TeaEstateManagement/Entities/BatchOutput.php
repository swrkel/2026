<?php
namespace Modules\TeaEstateManagement\Entities;
class BatchOutput extends BaseTeaModel
{
    protected $table = 'tea_batch_outputs';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
