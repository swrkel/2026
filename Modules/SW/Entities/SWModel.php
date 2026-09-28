<?php

namespace Modules\SW\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Base for SW models.
 *
 * No connection is declared, deliberately. tenant.context points the default
 * connection at the tenant for the life of the request, so following the
 * default is what keeps this module's data in the tenant database.
 *
 * Declaring a connection here is how Poultry came to write every farm into the
 * central database: its models followed a default that had not been repointed,
 * because its routes lacked tenant.context. The middleware is the fix, not a
 * hard-coded connection.
 */
abstract class SWModel extends Model
{
    protected $guarded = ['id'];
}
