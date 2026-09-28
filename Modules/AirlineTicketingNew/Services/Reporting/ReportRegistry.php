<?php
namespace Modules\AirlineTicketingNew\Services\Reporting;

class ReportRegistry
{
    public function all(): array
    {
        return [
            'ticket_sales' => 'Ticket Sales',
            'profitability' => 'Ticket Profitability',
            'airline_sales' => 'Airline Sales',
            'outstanding' => 'Outstanding Invoices',
            'refunds' => 'Refunds',
            'reissues' => 'Reissues',
            'bsp' => 'BSP Settlement',
            'commissions' => 'Commissions',
            'supplier_payables' => 'Supplier Payables',
            'corporate_aging' => 'Corporate Aging',
        ];
    }
}
