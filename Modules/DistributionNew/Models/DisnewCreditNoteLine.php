<?php

namespace Modules\DistributionNew\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DisnewCreditNoteLine extends Model
{
    use SoftDeletes;

    protected $table = 'disnew_credit_note_lines';
    protected $guarded = ['id'];
}
