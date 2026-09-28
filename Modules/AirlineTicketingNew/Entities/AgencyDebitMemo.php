<?php
namespace Modules\AirlineTicketingNew\Entities;

class AgencyDebitMemo extends BaseAirlineTicketingModel
{
    protected $table = 'atn_agency_debit_memos';
    protected $guarded = ['id'];
    protected $casts = [
        'memo_date' => 'date',
        'due_date' => 'date',
        'amount' => 'decimal:4',
        'disputed_amount' => 'decimal:4',
    ];
}
