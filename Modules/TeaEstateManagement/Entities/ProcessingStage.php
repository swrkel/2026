<?php
namespace Modules\TeaEstateManagement\Entities;
class ProcessingStage extends BaseTeaModel
{
    protected $table = 'tea_processing_stages';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
