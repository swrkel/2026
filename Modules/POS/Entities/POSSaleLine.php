<?php
namespace Modules\POS\Entities;
use Illuminate\Database\Eloquent\Model;
class POSSaleLine extends Model
{

    /*
     * MA-002: explicit table name.
     *
     * Without this, Laravel derives the table from the CLASS NAME. "POSSaleLine"
     * snake_cases to "p_o_s_sale_lines" - every capital gets its own
     * underscore - so this model pointed at a table that does not exist. The
     * migration creates `pos_sale_lines`, and that is what the database has:
     *
     *     pos_sale_lines         EXISTS
     *     p_o_s_sale_lines       missing
     *
     * It has not caused a failure yet only because nothing uses this model -
     * the POS services all go through the query builder on the correct table
     * name. The first person to write POSSaleLine::where(...) would have got
     * "Base table or view not found".
     *
     * Five models in this module had the same fault: POSSale, POSSaleLine,
     * POSPayment, POSRegister and POSHold. Acronym class names are the common
     * thread - the eleven entities that DO declare $table are unaffected.
     */
    protected $table = 'pos_sale_lines';

    protected $guarded = [];
}
