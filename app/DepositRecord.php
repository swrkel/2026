<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DepositRecord extends Model
{
    protected $table = 'deposit_records';

    protected $fillable = [
        'business_id',
        'location_id',
        'deposit_number',
        'contact_id',
        'current_loan_id',
        'deposit_type_id',
        'deposit_period',
        'deposit_period_value',
        'interest_per',
        'total_interest',
        'currency',
        'amount',
        'payment_method',
        'card_number',
        'slip_number',
        'bank',
        'cheque_no',
        'cheque_date',
        'deposited_bank_id',
        'transaction_reference',
        'attachment',
        'note',
        'created_by'
    ];

    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    public function location()
    {
        return $this->belongsTo(\App\BusinessLocation::class, 'location_id');
    }

    public function contact()
    {
        return $this->belongsTo(\App\Contact::class, 'contact_id');
    }

    public function depositType()
    {
        return $this->belongsTo(\App\DepositType::class, 'deposit_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'created_by');
    }
}
