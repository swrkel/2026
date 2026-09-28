<?php

namespace Modules\Purchase\Entities;

use Illuminate\Database\Eloquent\Model;

class PurchaseApproval extends Model
{
    protected $table = 'purchase_approvals';
    protected $guarded = ['id'];
}
