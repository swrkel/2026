<?php

namespace Modules\PetroDirect\Entities;

use Illuminate\Database\Eloquent\Model;

class IssueCustomerBillSetting extends Model
{
    /**
     * Table used for customer bill issue settings.
     */
    protected $table = 'issue_customer_bill_settings';

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];
}
