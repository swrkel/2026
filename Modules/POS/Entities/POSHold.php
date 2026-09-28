<?php
namespace Modules\POS\Entities;
use Illuminate\Database\Eloquent\Model;
class POSHold extends Model
{

    /*
     * MA-002: explicit table name.
     *
     * Without this, Laravel derives the table from the CLASS NAME. "POSHold"
     * snake_cases to "p_o_s_holds" - every capital gets its own
     * underscore - so this model pointed at a table that does not exist. The
     * migration creates `pos_holds`, and that is what the database has:
     *
     *     pos_holds              EXISTS
     *     p_o_s_holds            missing
     *
     * It has not caused a failure yet only because nothing uses this model -
     * the POS services all go through the query builder on the correct table
     * name. The first person to write POSHold::where(...) would have got
     * "Base table or view not found".
     *
     * Five models in this module had the same fault: POSSale, POSSaleLine,
     * POSPayment, POSRegister and POSHold. Acronym class names are the common
     * thread - the eleven entities that DO declare $table are unaffected.
     */
    protected $table = 'pos_holds';

    protected $guarded = [];
}
