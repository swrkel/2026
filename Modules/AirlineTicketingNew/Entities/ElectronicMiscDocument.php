<?php
namespace Modules\AirlineTicketingNew\Entities;

class ElectronicMiscDocument extends BaseAirlineTicketingModel
{
    protected $table = 'atn_electronic_misc_documents';
    protected $guarded = ['id'];
    protected $casts = [
        'issue_date' => 'date',
        'amount' => 'decimal:4',
    ];
}
