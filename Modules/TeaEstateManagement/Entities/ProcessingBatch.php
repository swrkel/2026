<?php
namespace Modules\TeaEstateManagement\Entities;
class ProcessingBatch extends BaseTeaModel
{
    protected $table = 'tea_processing_batches';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
