<?php
namespace Modules\AirlineTicketingNew\Entities;

class ManagedDocumentVersion extends BaseAirlineTicketingModel
{
    protected $table = 'atn_managed_document_versions';
    protected $guarded = ['id'];
}
