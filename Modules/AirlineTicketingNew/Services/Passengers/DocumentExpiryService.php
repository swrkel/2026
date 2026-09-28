<?php

namespace Modules\AirlineTicketingNew\Services\Passengers;

use Illuminate\Support\Facades\DB;

class DocumentExpiryService
{
    public function expiring(int $businessId, int $days = 90)
    {
        $from = now()->toDateString();
        $to = now()->addDays($days)->toDateString();

        $documents = DB::table('atn_passenger_documents as d')
            ->join('atn_passengers as p', 'p.id', '=', 'd.passenger_id')
            ->where('d.business_id', $businessId)
            ->whereBetween('d.expiry_date', [$from, $to])
            ->selectRaw("'document' source_type, d.expiry_date, d.document_type item_type, d.document_number item_number, p.passenger_no, CONCAT_WS(' ', p.first_name, p.last_name) passenger_name");

        $visas = DB::table('atn_passenger_visas as v')
            ->join('atn_passengers as p', 'p.id', '=', 'v.passenger_id')
            ->where('v.business_id', $businessId)
            ->whereBetween('v.expiry_date', [$from, $to])
            ->selectRaw("'visa' source_type, v.expiry_date, v.visa_type item_type, v.visa_number item_number, p.passenger_no, CONCAT_WS(' ', p.first_name, p.last_name) passenger_name");

        return $documents->unionAll($visas)->orderBy('expiry_date')->get();
    }
}
