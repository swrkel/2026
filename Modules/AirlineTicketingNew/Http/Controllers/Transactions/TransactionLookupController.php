<?php

namespace Modules\AirlineTicketingNew\Http\Controllers\Transactions;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\CorporateCustomer;
use Modules\AirlineTicketingNew\Entities\Passenger;

class TransactionLookupController extends Controller
{
    public function passengers(Request $request)
    {
        $search = $request->string('q')->toString();

        return Passenger::query()
            ->forBusiness()
            ->active()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('passenger_no', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'text' => trim($row->passenger_no . ' - ' . $row->first_name . ' ' . $row->last_name),
            ]);
    }

    public function corporateCustomers(Request $request)
    {
        $search = $request->string('q')->toString();

        return CorporateCustomer::query()
            ->forBusiness()
            ->active()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('customer_no', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->limit(30)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'text' => $row->customer_no . ' - ' . $row->company_name,
            ]);
    }
}
