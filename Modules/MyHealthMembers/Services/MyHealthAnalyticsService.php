<?php

namespace Modules\MyHealthMembers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MyHealthAnalyticsService
{
    public function dashboard(array $filters = []): array
    {
        $from = !empty($filters['date_from']) ? Carbon::parse($filters['date_from'])->startOfDay() : now()->startOfMonth();
        $to = !empty($filters['date_to']) ? Carbon::parse($filters['date_to'])->endOfDay() : now()->endOfDay();

        return [
            'filters' => [
                'date_from' => $from->toDateString(),
                'date_to' => $to->toDateString(),
            ],
            'kpis' => $this->kpis($from, $to),
            'clinical' => $this->clinicalSummary($from, $to),
            'operations' => $this->operationalSummary($from, $to),
            'financial' => $this->financialSummary($from, $to),
            'public_health' => $this->publicHealthSummary($from, $to),
        ];
    }

    private function kpis(Carbon $from, Carbon $to): array
    {
        return [
            'total_members' => $this->count('myhealth_members'),
            'new_members' => $this->count('myhealth_members', 'created_at', $from, $to),
            'consultations' => $this->count('myhealth_consultations', 'created_at', $from, $to),
            'lab_requests' => $this->count('myhealth_lab_requests', 'created_at', $from, $to),
            'radiology_requests' => $this->count('myhealth_radiology_requests', 'created_at', $from, $to),
            'surgeries' => $this->count('myhealth_surgery_schedules', 'created_at', $from, $to),
            'vaccinations' => $this->count('myhealth_vaccination_records', 'created_at', $from, $to),
            'insurance_claims' => $this->count('myhealth_insurance_claims', 'created_at', $from, $to),
            'invoices' => $this->count('myhealth_billing_invoices', 'created_at', $from, $to),
            'revenue' => $this->sum('myhealth_billing_payments', 'amount', 'created_at', $from, $to),
        ];
    }

    private function clinicalSummary(Carbon $from, Carbon $to): array
    {
        return [
            'top_diagnoses' => $this->top('myhealth_diagnoses', 'diagnosis', $from, $to),
            'top_medicines' => $this->top('myhealth_prescriptions', 'medicine_name', $from, $to),
            'top_lab_tests' => $this->top('myhealth_lab_requests', 'test_name', $from, $to),
            'vaccination_coverage' => $this->top('myhealth_vaccination_records', 'vaccine_name', $from, $to),
        ];
    }

    private function operationalSummary(Carbon $from, Carbon $to): array
    {
        return [
            'doctor_workload' => $this->top('myhealth_consultations', 'doctor_id', $from, $to),
            'lab_status' => $this->top('myhealth_lab_samples', 'status', $from, $to),
            'radiology_status' => $this->top('myhealth_radiology_requests', 'status', $from, $to),
            'theatre_status' => $this->top('myhealth_surgery_schedules', 'status', $from, $to),
        ];
    }

    private function financialSummary(Carbon $from, Carbon $to): array
    {
        return [
            'invoice_total' => $this->sum('myhealth_billing_invoices', 'total_amount', 'created_at', $from, $to),
            'payment_total' => $this->sum('myhealth_billing_payments', 'amount', 'created_at', $from, $to),
            'claim_total' => $this->sum('myhealth_claim_settlements', 'settlement_amount', 'created_at', $from, $to),
        ];
    }

    private function publicHealthSummary(Carbon $from, Carbon $to): array
    {
        return [
            'gender_distribution' => $this->top('myhealth_members', 'gender', $from, $to, 'created_at'),
            'blood_groups' => $this->top('myhealth_members', 'blood_group', $from, $to, 'created_at'),
            'chronic_conditions' => $this->top('myhealth_chronic_conditions', 'condition_name', $from, $to),
            'allergies' => $this->top('myhealth_allergies', 'allergy_name', $from, $to),
        ];
    }

    private function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return $this->tableExists($table) && DB::getSchemaBuilder()->hasColumn($table, $column);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function count(string $table, ?string $dateColumn = null, ?Carbon $from = null, ?Carbon $to = null): int
    {
        if (!$this->tableExists($table)) {
            return 0;
        }

        $query = DB::table($table);
        if ($dateColumn && $from && $to && $this->columnExists($table, $dateColumn)) {
            $query->whereBetween($dateColumn, [$from, $to]);
        }

        return (int) $query->count();
    }

    private function sum(string $table, string $column, ?string $dateColumn = null, ?Carbon $from = null, ?Carbon $to = null): float
    {
        if (!$this->columnExists($table, $column)) {
            return 0.0;
        }

        $query = DB::table($table);
        if ($dateColumn && $from && $to && $this->columnExists($table, $dateColumn)) {
            $query->whereBetween($dateColumn, [$from, $to]);
        }

        return (float) $query->sum($column);
    }

    private function top(string $table, string $column, Carbon $from, Carbon $to, string $dateColumn = 'created_at', int $limit = 10): array
    {
        if (!$this->columnExists($table, $column)) {
            return [];
        }

        $query = DB::table($table)
            ->select($column . ' as label', DB::raw('COUNT(*) as total'))
            ->whereNotNull($column)
            ->where($column, '!=', '');

        if ($this->columnExists($table, $dateColumn)) {
            $query->whereBetween($dateColumn, [$from, $to]);
        }

        return $query->groupBy($column)
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(function ($row) {
                return [
                    'label' => (string) $row->label,
                    'total' => (int) $row->total,
                ];
            })
            ->toArray();
    }
}
