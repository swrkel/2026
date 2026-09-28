<?php

namespace Modules\SW\Entities;

/**
 * 8043: the shifts a settlement covers.
 *
 * A unique key on sw_shift_id alone stops the same shift being settled twice -
 * which would double-count its collections, and is the kind of error that shows
 * up as an unexplained surplus weeks later.
 */
class SettlementShift extends SWModel
{
    protected $table = 'sw_settlement_shifts';
}
