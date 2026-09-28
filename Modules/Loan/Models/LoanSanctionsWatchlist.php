<?php

namespace Modules\Loan\Models;

use Illuminate\Database\Eloquent\Model;

class LoanSanctionsWatchlist extends Model
{
    protected $table = 'loan_sanctions_watchlists';

    protected $guarded = [];

    /*
    |--------------------------------------------------------------------------
    | AML/KYC Profile
    |--------------------------------------------------------------------------
    */

    public function amlKycProfile()
    {
        return $this->belongsTo(
            LoanAmlKycProfile::class,
            'aml_kyc_profile_id'
        );
    }
}