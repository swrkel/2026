<?php

namespace Modules\MPCS\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * MA-002: MPCS-owned model for the SHARED `hrm_designations` table.
 *
 * MPCS previously imported Modules\Essentials\Entities\HrmDesignation,
 * which meant the Authorized Signature screen could not work unless the
 * Essentials module was installed.
 *
 * Designations are shared reference data, so this maps to the same table
 * and the rows stay common with Essentials/HRM.
 *
 * NOT read-only: SignatureController creates designation rows (see
 * SignatureController::store and the designation lookup helpers), so the
 * $fillable list below is kept IDENTICAL to the Essentials model to
 * guarantee the same mass-assignment behaviour. If a column is ever added
 * to hrm_designations, update both models together.
 *
 * The department() relationship from the Essentials model is intentionally
 * omitted: MPCS never traverses it, and including it would pull
 * Modules\Essentials\Entities\HrmDepartment straight back in.
 */
class HrmDesignation extends Model
{
    protected $table = 'hrm_designations';

    protected $fillable = [
        'business_id',
        'department_id',
        'name',
        'created_by',
        'description',
    ];
}
