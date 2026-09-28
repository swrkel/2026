<?php

namespace Modules\Loan\Entities;

use Illuminate\Database\Eloquent\Model;

class LoanOfficer extends Model
{
    protected $table = 'loan_officers';

    protected $fillable = [
        'business_id',
        'user_id',
        'name',
        'email',
        'mobile',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];
}
