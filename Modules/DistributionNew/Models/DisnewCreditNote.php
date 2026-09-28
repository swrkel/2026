<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewCreditNote extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_credit_notes';
    protected $guarded = ['id'];
}
