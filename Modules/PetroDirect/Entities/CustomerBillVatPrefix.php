<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerBillVatPrefix extends Model
{
    /**
     * Table used by the original Petro module for Prefix and Starting Numbers.
     * PetroDirect reuses this existing tenant table instead of creating a new table.
     */
    protected $table = 'customer_bill_vat_prefixes';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
