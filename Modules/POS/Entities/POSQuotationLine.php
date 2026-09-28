<?php

namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class POSQuotationLine extends Model
{
    use SoftDeletes;

    protected $table = 'pos_quotation_lines';
    protected $guarded = ['id'];
}
