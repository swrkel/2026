<?php

namespace Modules\Vat\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: Vat-owned model for the SHARED `help_explanations` table.
 *
 * Vat previously imported Modules\Superadmin\Entities\HelpExplanation, which meant Vat could not run
 * without that module installed. The DATA stays shared - this maps to
 * the same table and the same rows - but the CODE dependency is gone.
 *
 * READ-ONLY BY CONTRACT. Vat only reads help text.
 * */
class HelpExplanation extends Model
{
    protected $table = 'help_explanations';

    protected $guarded = ['id'];
}
