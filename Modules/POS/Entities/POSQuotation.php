<?php

namespace Modules\POS\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class POSQuotation extends Model
{
    use SoftDeletes;

    protected $table = 'pos_quotations';
    protected $guarded = ['id'];
}
