<?php
namespace Modules\TeaEstateManagement\Entities;
class FinanceEvent extends BaseTeaModel
{
    protected $table = 'tea_finance_events';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
