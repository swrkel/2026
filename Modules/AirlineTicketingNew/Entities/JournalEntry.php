<?php
namespace Modules\AirlineTicketingNew\Entities;
class JournalEntry extends BaseAirlineTicketingModel {
    protected $table='atn_journal_entries';
    protected $guarded=['id'];
    protected $casts=['journal_date'=>'date','exchange_rate'=>'decimal:8','total_debit'=>'decimal:4','total_credit'=>'decimal:4','posted_at'=>'datetime'];
    public function lines(){ return $this->hasMany(JournalLine::class,'journal_entry_id'); }
}
