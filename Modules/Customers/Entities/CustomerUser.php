<?php

namespace Modules\Customers\Entities;

use Illuminate\Database\Eloquent\Model;

class CustomerUser extends Model
{
    protected $table = 'users';

    protected $guarded = ['id'];
}
