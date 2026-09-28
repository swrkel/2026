<?php
namespace Modules\TeaEstateManagement\Entities;
class FinanceAccountMapping extends BaseTeaModel
{
    protected $table = 'tea_finance_account_mappings';
    protected $casts = ['created_at'=>'datetime','updated_at'=>'datetime'];
}
