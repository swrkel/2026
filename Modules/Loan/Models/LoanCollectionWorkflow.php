<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanCollectionWorkflow extends Model
{
    protected $table = 'loan_collection_workflows';

    protected $guarded = [];

    public function loan()
    {
        return $this->belongsTo(
            Loan::class,
            'loan_id'
        );
    }

    public function customer()
    {
        return $this->belongsTo(
            \App\Contact::class,
            'customer_id'
        );
    }

    public function location()
    {
        return $this->belongsTo(
            \App\BusinessLocation::class,
            'location_id'
        );
    }

    public function assignedTo()
    {
        return $this->belongsTo(
            \App\User::class,
            'assigned_to'
        );
    }

    public function createdBy()
    {
        return $this->belongsTo(
            \App\User::class,
            'created_by'
        );
    }
}