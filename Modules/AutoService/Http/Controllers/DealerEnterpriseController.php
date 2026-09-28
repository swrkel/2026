<?php

namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DealerEnterpriseController extends AutoServiceBaseController
{
    public function index(Request $request)
    {
        $businessId = $this->businessId();
        $locationId = $this->locationId();
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $search = trim((string) $request->get('search'));

        $fleetCustomers = $this->fleetCustomers($search);
        $contracts = $this->fleetContracts($from, $to, $search);
        $servicePackages = $this->servicePackages($search);
        $drivers = $this->drivers($search);
        $corporatePricing = $this->corporatePricing($search);
        $fleetJobs = $this->fleetJobs($from, $to, $search);

        $summary = [
            'fleet_customers' => $fleetCustomers->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'service_packages' => $servicePackages->count(),
            'drivers' => $drivers->count(),
            'fleet_jobs' => $fleetJobs->count(),
            'fleet_revenue' => (float) $fleetJobs->sum('total_amount'),
        ];

        return view('autoservice::dealer_enterprise.index', compact(
            'from', 'to', 'search', 'summary', 'fleetCustomers', 'contracts', 'servicePackages', 'drivers', 'corporatePricing', 'fleetJobs'
        ));
    }

    public function storeFleetCustomer(Request $request)
    {
        $data = $request->validate([
            'contact_id' => 'nullable|integer',
            'fleet_name' => 'required|string|max:191',
            'contract_no' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric',
            'billing_cycle' => 'nullable|string|max:50',
            'status' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        DB::table('auto_service_fleet_customers')->insert(array_merge($data, [
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('autoservice.dealer_enterprise.index')->with('status', 'Fleet customer saved successfully.');
    }

    public function storeContract(Request $request)
    {
        $data = $request->validate([
            'fleet_customer_id' => 'required|integer',
            'contract_no' => 'required|string|max:100',
            'contract_type' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'vehicle_limit' => 'nullable|integer',
            'monthly_value' => 'nullable|numeric',
            'status' => 'nullable|string|max:50',
            'note' => 'nullable|string',
        ]);

        DB::table('auto_service_fleet_contracts')->insert(array_merge($data, [
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('autoservice.dealer_enterprise.index')->with('status', 'Fleet service contract saved successfully.');
    }

    public function storeDriver(Request $request)
    {
        $data = $request->validate([
            'fleet_customer_id' => 'required|integer',
            'driver_name' => 'required|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'nic_no' => 'nullable|string|max:100',
            'license_no' => 'nullable|string|max:100',
            'assigned_vehicle_id' => 'nullable|integer',
            'status' => 'nullable|string|max:50',
        ]);

        DB::table('auto_service_fleet_drivers')->insert(array_merge($data, [
            'business_id' => $this->businessId(),
            'location_id' => $this->locationId(),
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return redirect()->route('autoservice.dealer_enterprise.index')->with('status', 'Fleet driver saved successfully.');
    }

    public function exportFleetJobs(Request $request): StreamedResponse
    {
        $from = $request->get('from') ?: date('Y-m-01');
        $to = $request->get('to') ?: date('Y-m-d');
        $search = trim((string) $request->get('search'));
        $rows = $this->fleetJobs($from, $to, $search, 5000);

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Fleet Customer', 'Job No', 'Vehicle', 'Driver', 'Status', 'Invoice No', 'Total Amount', 'Paid Amount']);
            foreach ($rows as $row) {
                fputcsv($out, [$row->job_date, $row->fleet_name, $row->job_no, $row->registration_no, $row->driver_name, $row->status, $row->invoice_no, $row->total_amount, $row->paid_amount]);
            }
            fclose($out);
        }, 'autoservice_fleet_jobs_' . date('Ymd_His') . '.csv');
    }

    protected function fleetCustomers(string $search)
    {
        if (!Schema::hasTable('auto_service_fleet_customers')) {
            return collect();
        }

        return DB::table('auto_service_fleet_customers as f')
            ->leftJoin('contacts as c', 'c.id', '=', 'f.contact_id')
            ->select('f.*', 'c.name as contact_name', 'c.mobile as contact_mobile')
            ->when($this->businessId(), fn($q) => $q->where('f.business_id', $this->businessId()))
            ->when($this->locationId(), fn($q) => $q->where(function ($qq) { $qq->whereNull('f.location_id')->orWhere('f.location_id', $this->locationId()); }))
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) {
                $qq->where('f.fleet_name', 'like', "%{$search}%")
                   ->orWhere('f.contract_no', 'like', "%{$search}%")
                   ->orWhere('c.name', 'like', "%{$search}%")
                   ->orWhere('c.mobile', 'like', "%{$search}%");
            }))
            ->orderByDesc('f.id')
            ->limit(50)
            ->get();
    }

    protected function fleetContracts(string $from, string $to, string $search)
    {
        if (!Schema::hasTable('auto_service_fleet_contracts')) {
            return collect();
        }

        return DB::table('auto_service_fleet_contracts as fc')
            ->leftJoin('auto_service_fleet_customers as f', 'f.id', '=', 'fc.fleet_customer_id')
            ->select('fc.*', 'f.fleet_name')
            ->when($this->businessId(), fn($q) => $q->where('fc.business_id', $this->businessId()))
            ->when($this->locationId(), fn($q) => $q->where(function ($qq) { $qq->whereNull('fc.location_id')->orWhere('fc.location_id', $this->locationId()); }))
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) {
                $qq->where('fc.contract_no', 'like', "%{$search}%")
                   ->orWhere('fc.contract_type', 'like', "%{$search}%")
                   ->orWhere('f.fleet_name', 'like', "%{$search}%");
            }))
            ->orderByDesc('fc.id')
            ->limit(50)
            ->get();
    }

    protected function servicePackages(string $search)
    {
        if (!Schema::hasTable('auto_service_service_packages')) {
            return collect();
        }

        return DB::table('auto_service_service_packages')
            ->when($this->businessId(), fn($q) => $q->where('business_id', $this->businessId()))
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) {
                $qq->where('name', 'like', "%{$search}%")->orWhere('code', 'like', "%{$search}%");
            }))
            ->orderByDesc('id')
            ->limit(50)
            ->get();
    }

    protected function drivers(string $search)
    {
        if (!Schema::hasTable('auto_service_fleet_drivers')) {
            return collect();
        }

        return DB::table('auto_service_fleet_drivers as d')
            ->leftJoin('auto_service_fleet_customers as f', 'f.id', '=', 'd.fleet_customer_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'd.assigned_vehicle_id')
            ->select('d.*', 'f.fleet_name', 'v.registration_no')
            ->when($this->businessId(), fn($q) => $q->where('d.business_id', $this->businessId()))
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) {
                $qq->where('d.driver_name', 'like', "%{$search}%")
                   ->orWhere('d.mobile', 'like', "%{$search}%")
                   ->orWhere('d.license_no', 'like', "%{$search}%")
                   ->orWhere('v.registration_no', 'like', "%{$search}%");
            }))
            ->orderByDesc('d.id')
            ->limit(50)
            ->get();
    }

    protected function corporatePricing(string $search)
    {
        if (!Schema::hasTable('auto_service_corporate_pricing')) {
            return collect();
        }

        return DB::table('auto_service_corporate_pricing as p')
            ->leftJoin('auto_service_fleet_customers as f', 'f.id', '=', 'p.fleet_customer_id')
            ->select('p.*', 'f.fleet_name')
            ->when($this->businessId(), fn($q) => $q->where('p.business_id', $this->businessId()))
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) {
                $qq->where('p.item_name', 'like', "%{$search}%")
                   ->orWhere('p.item_code', 'like', "%{$search}%")
                   ->orWhere('f.fleet_name', 'like', "%{$search}%");
            }))
            ->orderByDesc('p.id')
            ->limit(50)
            ->get();
    }

    protected function fleetJobs(string $from, string $to, string $search, int $limit = 50)
    {
        if (!Schema::hasTable('auto_service_jobs')) {
            return collect();
        }

        return DB::table('auto_service_jobs as j')
            ->leftJoin('auto_service_fleet_customers as f', 'f.id', '=', 'j.fleet_customer_id')
            ->leftJoin('auto_service_fleet_drivers as d', 'd.id', '=', 'j.driver_id')
            ->leftJoin('auto_service_vehicles as v', 'v.id', '=', 'j.vehicle_id')
            ->leftJoin('auto_service_invoices as i', 'i.job_id', '=', 'j.id')
            ->select(
                DB::raw('date(j.created_at) as job_date'),
                'f.fleet_name', 'j.job_no', 'j.status', 'v.registration_no', 'd.driver_name',
                'i.invoice_no', DB::raw('COALESCE(i.total_amount,0) as total_amount'), DB::raw('COALESCE(i.paid_amount,0) as paid_amount')
            )
            ->when($this->businessId(), fn($q) => $q->where('j.business_id', $this->businessId()))
            ->when($this->locationId(), fn($q) => $q->where(function ($qq) { $qq->whereNull('j.location_id')->orWhere('j.location_id', $this->locationId()); }))
            ->whereBetween('j.created_at', [$from . ' 00:00:00', $to . ' 23:59:59'])
            ->where(function ($q) {
                $q->whereNotNull('j.fleet_customer_id')->orWhereNotNull('j.driver_id');
            })
            ->when($search, fn($q) => $q->where(function ($qq) use ($search) {
                $qq->where('f.fleet_name', 'like', "%{$search}%")
                   ->orWhere('j.job_no', 'like', "%{$search}%")
                   ->orWhere('v.registration_no', 'like', "%{$search}%")
                   ->orWhere('d.driver_name', 'like', "%{$search}%");
            }))
            ->orderByDesc('j.id')
            ->limit($limit)
            ->get();
    }
}
