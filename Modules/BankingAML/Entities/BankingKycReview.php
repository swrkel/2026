<?php

namespace Modules\BankingAML\Entities;

use Illuminate\Database\Eloquent\Model;

class BankingKycReview extends Model
{
    protected $table = 'bkg_kyc_reviews';
    protected $guarded = [];
}
