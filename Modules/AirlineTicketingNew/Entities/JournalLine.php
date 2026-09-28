<?php
namespace Modules\AirlineTicketingNew\Entities;
class JournalLine extends BaseAirlineTicketingModel {
    protected $table='atn_journal_lines';
    protected $guarded=['id'];
    protected $casts=['debit'=>'decimal:4','credit'=>'decimal:4','base_debit'=>'decimal:4','base_credit'=>'decimal:4'];
}
