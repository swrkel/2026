<?php
namespace Modules\BeautySaloons\Entities;
use Illuminate\Database\Eloquent\Model;

class BeautyFinancePosting extends Model
{
    protected $table = 'beauty_finance_postings';
    protected $guarded = ['id'];
    protected $casts = ['posting_date' => 'date', 'debit' => 'decimal:4', 'credit' => 'decimal:4'];
}
