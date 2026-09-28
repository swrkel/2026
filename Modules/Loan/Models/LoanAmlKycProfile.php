<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanAmlKycProfile extends Model
{
    protected $table = 'loan_aml_kyc_profiles';

    protected $fillable = [
        'business_id',
        'borrower_id',
        'kyc_status',
        'aml_status',
        'risk_rating',
        'customer_type',
        'pep_flag',
        'sanctions_flag',
        'adverse_media_flag',
        'source_of_funds',
        'source_of_wealth',
        'occupation',
        'annual_income',
        'kyc_verified_at',
        'aml_reviewed_at',
        'next_review_date',
        'compliance_notes',
        'approved_by',
        'rejected_reason',
        'created_by',
        'updated_by'
    ];

    public function borrower()
    {
        return $this->belongsTo(\App\Contact::class, 'borrower_id');
    }
}