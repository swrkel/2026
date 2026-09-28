<?php
namespace Modules\PetroDirect\Exports;
class CollectionExport extends BaseExport
{
    public function __construct($query)
    {
        parent::__construct($query, ['Collection No', 'Date', 'Location', 'Settlement No', 'Amount'], ['collection_form_no', 'collection_date', 'location_name', 'settlement_no', 'amount']);
    }
}
