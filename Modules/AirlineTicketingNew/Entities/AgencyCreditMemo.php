<?php
namespace Modules\AirlineTicketingNew\Entities;

class AgencyCreditMemo extends BaseAirlineTicketingModel
{
    protected $table = 'atn_agency_credit_memos';
    protected $guarded = ['id'];
    protected $casts = [
        'memo_date' => 'date',
        'amount' => 'decimal:4',
    ];
}
