<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoanBankCustomer extends Model
{
    protected $table = 'loan_bank_customers';

    protected $guarded = ['id'];

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
