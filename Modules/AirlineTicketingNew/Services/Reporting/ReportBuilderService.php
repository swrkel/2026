<?php
namespace Modules\AirlineTicketingNew\Services\Reporting;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\AirlineTicketingNew\Entities\ReportTemplate;

class ReportBuilderService
{
    private array $allowedTables = [
        'atn_tickets','atn_invoices','atn_payments','atn_refunds',
        'atn_reservations','atn_supplier_settlements','atn_ticket_profits',
    ];

    public function build(ReportTemplate $template)
    {
        $table = data_get($template->data_source_json, 'table');

        if (!in_array($table, $this->allowedTables, true)) {
            throw new InvalidArgumentException('Report data source is not allowed.');
        }

        $columns = $template->columns_json ?: ['*'];

        return DB::table($table)
            ->where('business_id', $template->business_id)
            ->select($columns);
    }
}
