<?php
namespace Modules\Ran\Entities;
class FinancePosting extends RanModel {
    protected $table = 'ran_finance_postings';
    protected $casts = ['amount'=>'decimal:4'];

}
