<?php

namespace Modules\BankingMicrofinanceTreasury\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TreasuryInterBranchTransfer extends Model
{
    use SoftDeletes;
    protected $guarded = [];
    protected $casts = ['bucket_summary' => 'array', 'approved_at' => 'datetime', 'posted_at' => 'datetime'];
}
