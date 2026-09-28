<?php

namespace Modules\AirlineTicketingNew\Entities\Relations;

use Modules\AirlineTicketingNew\Entities\QuotationSegment;

trait QuotationRelations
{
    public function segments()
    {
        return $this->hasMany(QuotationSegment::class, 'quotation_id')->orderBy('segment_no');
    }
}
