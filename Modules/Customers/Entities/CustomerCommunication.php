<?php

namespace Modules\Customers\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CustomerCommunication
 *
 * Standalone Customers module foundation model for future communication
 * history. Kept inside Modules/Customers so customer communications do not
 * depend on generic ERP notes/CRM classes.
 */
class CustomerCommunication extends Model
{
    use SoftDeletes;

    protected $table = 'customer_communications';

    protected $guarded = ['id'];

    protected $dates = ['deleted_at'];
}
