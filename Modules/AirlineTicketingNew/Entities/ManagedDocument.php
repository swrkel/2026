<?php
namespace Modules\AirlineTicketingNew\Entities;

class ManagedDocument extends BaseAirlineTicketingModel
{
    protected $table = 'atn_managed_documents';
    protected $guarded = ['id'];
    protected $casts = [
        'expiry_date' => 'date',
        'metadata_json' => 'array',
        'is_confidential' => 'boolean',
    ];
}
