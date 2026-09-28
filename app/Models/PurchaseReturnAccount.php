<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnAccount extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function account()
    {
        return $this->belongsTo(\App\Account::class);
    }

    public function created_user()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }

    public function purchaseReturnAccount()
    {
        return $this->belongsTo(PurchaseReturnAccount::class, 'purchase_return_account_id');
    }

    public function transaction()
    {
        return $this->morphMany(\App\Transaction::class, 'transaction');
    }
}
