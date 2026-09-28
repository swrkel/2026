<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutoServiceAdvancedVehicleHistoryService
{
    protected function db()
    {
        try {
            if (function_exists('tenant_db')) tenant_db();
            if (!empty(config('database.connections.mysql_tenant.database'))) {
                return DB::connection('mysql_tenant');
            }
        } catch (\Throwable $e) {}
        return DB::connection();
    }

    protected function businessId()
    {
        return app(AutoServiceContext::class)->businessId();
    }

    protected function locationId()
    {
        return app(AutoServiceContext::class)->locationId();
    }

    protected function tableExists(string $table): bool
    {
        try { return Schema::connection($this->db()->getName())->hasTable($table); } catch (\Throwable $e) { return false; }
    }

    protected function columnExists(string $table, string $column): bool
    {
        try { return Schema::connection($this->db()->getName())->hasColumn($table, $column); } catch (\Throwable $e) { return false; }
    }

    protected function scope($query, string $alias = '')
    {
        $prefix = $alias ? $alias.'.' : '';
        if ($this->businessId() !== null) $query->where($prefix.'business_id', $this->businessId());
        if ($this->locationId() !== null) {
            try {
                $query->where(function($q) use ($prefix) {
                    $q->whereNull($prefix.'location_id')->orWhere($prefix.'location_id', $this->locationId());
                });
            } catch (\Throwable $e) {}
        }
        return $query;
    }

    public function searchVehicles(array $filters = [])
    {
        if (!$this->tableExists('auto_service_vehicles')) return collect();
        $q = $this->db()->table('auto_service_vehicles as v')
            ->select('v.*')
            ->whereNull('v.deleted_at');
        $this->scope($q, 'v');

        if (!empty($filters['registration_no'])) {
            $q->where('v.registration_no', 'like', '%'.$filters['registration_no'].'%');
        }
        if (!empty($filters['vin'])) {
            $q->where('v.vin', 'like', '%'.$filters['vin'].'%');
        }
        if (!empty($filters['customer_id'])) {
            $q->where('v.contact_id', $filters['customer_id']);
        }
        if (!empty($filters['make'])) {
            $q->where('v.make', 'like', '%'.$filters['make'].'%');
        }

        return $q->orderBy('v.updated_at', 'desc')->limit(100)->get();
    }

    public function buildHistory(int $vehicleId, array $filters = []): array
    {
        $vehicle = $this->vehicle($vehicleId);
        if (!$vehicle) {
            return ['vehicle' => null, 'summary' => [], 'jobs' => collect(), 'parts' => collect(), 'labour' => collect(), 'invoices' => collect(), 'documents' => collect(), 'warranty' => collect(), 'odometer' => collect(), 'timeline' => collect()];
        }

        return [
            'vehicle' => $vehicle,
            'summary' => $this->summary($vehicleId),
            'jobs' => $this->jobs($vehicleId, $filters),
            'parts' => $this->parts($vehicleId, $filters),
            'labour' => $this->labour($vehicleId, $filters),
            'invoices' => $this->invoices($vehicleId, $filters),
            'documents' => $this->documents($vehicleId, $filters),
            'warranty' => $this->warranty($vehicleId, $filters),
            'odometer' => $this->odometer($vehicleId),
            'timeline' => $this->timeline($vehicleId, $filters),
        ];
    }

    public function vehicle(int $vehicleId)
    {
        if (!$this->tableExists('auto_service_vehicles')) return null;
        $q = $this->db()->table('auto_service_vehicles as v')->where('v.id', $vehicleId)->whereNull('v.deleted_at');
        $this->scope($q, 'v');
        return $q->first();
    }

    public function summary(int $vehicleId): array
    {
        $jobs = $this->tableExists('auto_service_jobs') ? $this->baseJobs($vehicleId) : null;
        $invoiceTotal = 0; $paid = 0; $balance = 0; $jobCount = 0; $partsTotal = 0; $labourTotal = 0;
        if ($jobs) {
            $jobCount = (clone $jobs)->count();
            $invoiceTotal = (clone $jobs)->sum('total_amount');
            $paid = (clone $jobs)->sum('paid_amount');
            $balance = (clone $jobs)->sum('balance_amount');
        }
        if ($this->tableExists('auto_service_part_movements')) {
            $parts = $this->db()->table('auto_service_part_movements as pm')->join('auto_service_jobs as j','pm.job_id','=','j.id')->where('j.vehicle_id',$vehicleId);
            $this->scope($parts, 'pm');
            if ($this->columnExists('auto_service_part_movements','unit_price')) $partsTotal = $parts->sum(DB::raw('COALESCE(pm.line_total, (pm.quantity * COALESCE(pm.unit_price, pm.unit_cost, 0)) - COALESCE(pm.discount_amount,0))'));
            else $partsTotal = $parts->sum('pm.line_total');
        }
        if ($this->tableExists('auto_service_labour_items')) {
            $lab = $this->db()->table('auto_service_labour_items as l')->join('auto_service_jobs as j','l.job_id','=','j.id')->where('j.vehicle_id',$vehicleId);
            $this->scope($lab, 'l');
            $labourTotal = $lab->sum('l.line_total');
        }
        return compact('jobCount','invoiceTotal','paid','balance','partsTotal','labourTotal');
    }

    protected function baseJobs(int $vehicleId)
    {
        $q = $this->db()->table('auto_service_jobs as j')->where('j.vehicle_id', $vehicleId)->whereNull('j.deleted_at');
        $this->scope($q, 'j');
        return $q;
    }

    protected function applyDateFilters($q, array $filters, string $dateColumn)
    {
        if (!empty($filters['from_date'])) $q->whereDate($dateColumn, '>=', $filters['from_date']);
        if (!empty($filters['to_date'])) $q->whereDate($dateColumn, '<=', $filters['to_date']);
        return $q;
    }

    public function jobs(int $vehicleId, array $filters)
    {
        if (!$this->tableExists('auto_service_jobs')) return collect();
        $q = $this->baseJobs($vehicleId)->select('j.*');
        $this->applyDateFilters($q, $filters, 'j.job_date');
        if (!empty($filters['status'])) $q->where('j.status', $filters['status']);
        return $q->orderBy('j.job_date','desc')->orderBy('j.id','desc')->limit(200)->get();
    }

    public function parts(int $vehicleId, array $filters)
    {
        if (!$this->tableExists('auto_service_part_movements')) return collect();
        $q = $this->db()->table('auto_service_part_movements as pm')
            ->join('auto_service_jobs as j','pm.job_id','=','j.id')
            ->where('j.vehicle_id',$vehicleId)
            ->select('pm.*','j.job_no','j.job_date');
        $this->scope($q, 'pm');
        $this->applyDateFilters($q, $filters, 'pm.movement_date');
        if (!empty($filters['part'])) $q->where('pm.description','like','%'.$filters['part'].'%');
        if (!empty($filters['movement_type'])) $q->where('pm.movement_type',$filters['movement_type']);
        return $q->orderBy('pm.movement_date','desc')->orderBy('pm.id','desc')->limit(500)->get();
    }

    public function labour(int $vehicleId, array $filters)
    {
        if (!$this->tableExists('auto_service_labour_items')) return collect();
        $q = $this->db()->table('auto_service_labour_items as l')
            ->join('auto_service_jobs as j','l.job_id','=','j.id')
            ->where('j.vehicle_id',$vehicleId)
            ->select('l.*','j.job_no','j.job_date');
        $this->scope($q, 'l');
        $this->applyDateFilters($q, $filters, 'j.job_date');
        if (!empty($filters['labour'])) $q->where('l.description','like','%'.$filters['labour'].'%');
        return $q->orderBy('j.job_date','desc')->orderBy('l.id','desc')->limit(300)->get();
    }

    public function invoices(int $vehicleId, array $filters)
    {
        if (!$this->tableExists('auto_service_invoices')) return collect();
        $q = $this->db()->table('auto_service_invoices as i')
            ->join('auto_service_jobs as j','i.job_id','=','j.id')
            ->where('j.vehicle_id',$vehicleId)
            ->select('i.*','j.job_no','j.job_date');
        $this->scope($q, 'i');
        $this->applyDateFilters($q, $filters, 'i.invoice_date');
        return $q->orderBy('i.invoice_date','desc')->orderBy('i.id','desc')->limit(200)->get();
    }

    public function documents(int $vehicleId, array $filters)
    {
        if (!$this->tableExists('auto_service_documents')) return collect();
        $q = $this->db()->table('auto_service_documents as d')->where('d.vehicle_id',$vehicleId)->whereNull('d.deleted_at')->select('d.*');
        $this->scope($q, 'd');
        if (!empty($filters['document_type'])) $q->where('d.document_type',$filters['document_type']);
        return $q->orderBy('d.created_at','desc')->limit(200)->get();
    }

    public function warranty(int $vehicleId, array $filters)
    {
        if (!$this->tableExists('auto_service_warranty_claims')) return collect();
        $q = $this->db()->table('auto_service_warranty_claims as w')->where('w.vehicle_id',$vehicleId)->select('w.*');
        $this->scope($q, 'w');
        $this->applyDateFilters($q, $filters, 'w.created_at');
        return $q->orderBy('w.created_at','desc')->limit(200)->get();
    }

    public function odometer(int $vehicleId)
    {
        if (!$this->tableExists('auto_service_jobs')) return collect();
        $q = $this->baseJobs($vehicleId)->whereNotNull('j.odometer')->select('j.job_no','j.job_date','j.odometer','j.status');
        return $q->orderBy('j.job_date','asc')->orderBy('j.id','asc')->get();
    }

    public function timeline(int $vehicleId, array $filters)
    {
        if (!$this->tableExists('auto_service_timeline')) return collect();
        $q = $this->db()->table('auto_service_timeline as t')->where('t.vehicle_id',$vehicleId)->select('t.*');
        $this->scope($q, 't');
        $this->applyDateFilters($q, $filters, 't.event_at');
        return $q->orderBy('t.event_at','desc')->orderBy('t.id','desc')->limit(300)->get();
    }

    public function exportPartsCsv(int $vehicleId, array $filters): string
    {
        $rows = $this->parts($vehicleId, $filters);
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['Date','Job No','Movement','Part/Accessory','Qty','Unit Price','Discount','Tax','Total','Reference','Note']);
        foreach ($rows as $r) {
            $unitPrice = $r->unit_price ?? $r->unit_cost ?? 0;
            $discount = $r->discount_amount ?? 0;
            $tax = $r->tax_amount ?? 0;
            $total = $r->line_total ?? (($r->quantity ?? 0) * $unitPrice - $discount + $tax);
            fputcsv($handle, [$r->movement_date ?? $r->created_at, $r->job_no, $r->movement_type, $r->description, $r->quantity, $unitPrice, $discount, $tax, $total, $r->reference_no ?? '', $r->note ?? '']);
        }
        rewind($handle);
        return stream_get_contents($handle);
    }
}
