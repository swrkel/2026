<?php

namespace Modules\MyHealthMembers\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MyHealthAiClinicalAssistantService
{
    protected function connection()
    {
        return DB::connection(config('myhealthmembers.central_connection'));
    }

    protected function hasTable(string $table): bool
    {
        return Schema::connection(config('myhealthmembers.central_connection'))->hasTable($table);
    }

    protected function hasColumn(string $table, string $column): bool
    {
        return $this->hasTable($table) && Schema::connection(config('myhealthmembers.central_connection'))->hasColumn($table, $column);
    }

    public function dashboard(array $filters = []): array
    {
        return [
            'kpis' => [
                'open_alerts' => $this->countAlerts($filters, 'open'),
                'critical_alerts' => $this->countBySeverity($filters, 'critical'),
                'allergy_alerts' => $this->countByType($filters, 'allergy'),
                'medication_alerts' => $this->countByType($filters, 'medication'),
                'abnormal_lab_alerts' => $this->countByType($filters, 'lab'),
                'critical_radiology_alerts' => $this->countByType($filters, 'radiology'),
                'followup_reminders' => $this->countReminders($filters, 'follow_up'),
                'preventive_reminders' => $this->countReminders($filters, 'preventive_care'),
            ],
            'alerts' => $this->alerts($filters, 15),
            'high_risk_members' => $this->highRiskMembers($filters),
            'reminders' => $this->preventiveCare($filters, 10),
        ];
    }

    public function alerts(array $filters = [], int $limit = 50)
    {
        if (! $this->hasTable('myhealth_clinical_alerts')) {
            return collect();
        }

        $query = $this->connection()->table('myhealth_clinical_alerts as a')
            ->leftJoin('myhealth_members as m', 'm.id', '=', 'a.member_id')
            ->select('a.*', 'm.member_code', 'm.name as member_name', 'm.mobile')
            ->orderByRaw("FIELD(a.severity, 'critical', 'high', 'medium', 'low')")
            ->orderByDesc('a.created_at');

        $this->applyAlertFilters($query, $filters);

        return $query->limit($limit)->get();
    }

    public function timeline(?string $memberCode)
    {
        if (empty($memberCode) || ! $this->hasTable('myhealth_members')) {
            return collect();
        }

        $member = $this->connection()->table('myhealth_members')->where('member_code', $memberCode)->first();
        if (! $member) {
            return collect();
        }

        $rows = collect();
        $memberId = $member->id;

        $sources = [
            ['table' => 'myhealth_consultations', 'date' => 'created_at', 'title' => 'Consultation', 'detail' => 'chief_complaint'],
            ['table' => 'myhealth_prescriptions', 'date' => 'created_at', 'title' => 'Prescription', 'detail' => 'instructions'],
            ['table' => 'myhealth_lab_requests', 'date' => 'created_at', 'title' => 'Lab Request', 'detail' => 'test_name'],
            ['table' => 'myhealth_radiology_requests', 'date' => 'created_at', 'title' => 'Radiology', 'detail' => 'study_type'],
            ['table' => 'myhealth_surgery_schedules', 'date' => 'surgery_date', 'title' => 'Surgery', 'detail' => 'procedure_name'],
            ['table' => 'myhealth_vaccination_records', 'date' => 'vaccination_date', 'title' => 'Vaccination', 'detail' => 'vaccine_name'],
        ];

        foreach ($sources as $source) {
            if (! $this->hasTable($source['table']) || ! $this->hasColumn($source['table'], 'member_id')) {
                continue;
            }

            $dateColumn = $this->hasColumn($source['table'], $source['date']) ? $source['date'] : 'created_at';
            $detailColumn = $this->hasColumn($source['table'], $source['detail']) ? $source['detail'] : null;

            $records = $this->connection()->table($source['table'])
                ->where('member_id', $memberId)
                ->orderByDesc($dateColumn)
                ->limit(20)
                ->get();

            foreach ($records as $record) {
                $rows->push((object) [
                    'date' => $record->{$dateColumn} ?? null,
                    'type' => $source['title'],
                    'title' => $source['title'],
                    'details' => $detailColumn ? ($record->{$detailColumn} ?? '') : '',
                ]);
            }
        }

        return $rows->sortByDesc('date')->values();
    }

    public function medicationSafety(array $filters = [], int $limit = 100)
    {
        if (! $this->hasTable('myhealth_clinical_alerts')) {
            return collect();
        }

        $query = $this->connection()->table('myhealth_clinical_alerts as a')
            ->leftJoin('myhealth_members as m', 'm.id', '=', 'a.member_id')
            ->select('a.*', 'm.member_code', 'm.name as member_name')
            ->where(function ($q) {
                $q->where('a.alert_type', 'like', '%medication%')
                    ->orWhere('a.alert_type', 'like', '%drug%')
                    ->orWhere('a.alert_type', 'like', '%allergy%')
                    ->orWhere('a.title', 'like', '%medicine%')
                    ->orWhere('a.title', 'like', '%drug%');
            })
            ->orderByDesc('a.created_at');

        $this->applyAlertFilters($query, $filters);

        return $query->limit($limit)->get();
    }

    public function preventiveCare(array $filters = [], int $limit = 100)
    {
        if (! $this->hasTable('myhealth_ai_reminders')) {
            return collect();
        }

        $query = $this->connection()->table('myhealth_ai_reminders as r')
            ->leftJoin('myhealth_members as m', 'm.id', '=', 'r.member_id')
            ->select('r.*', 'm.member_code', 'm.name as member_name', 'm.mobile')
            ->orderByRaw("FIELD(r.priority, 'critical', 'high', 'medium', 'low')")
            ->orderBy('r.due_date');

        if (!empty($filters['member_code'])) {
            $query->where('m.member_code', $filters['member_code']);
        }

        return $query->limit($limit)->get();
    }

    protected function highRiskMembers(array $filters = [])
    {
        if (! $this->hasTable('myhealth_clinical_alerts')) {
            return collect();
        }

        return $this->connection()->table('myhealth_clinical_alerts as a')
            ->leftJoin('myhealth_members as m', 'm.id', '=', 'a.member_id')
            ->select('m.member_code', 'm.name as member_name', DB::raw('COUNT(a.id) as alert_count'), DB::raw("SUM(CASE WHEN a.severity IN ('critical','high') THEN 1 ELSE 0 END) as high_alert_count"))
            ->whereIn('a.severity', ['critical', 'high'])
            ->groupBy('m.member_code', 'm.name')
            ->orderByDesc('high_alert_count')
            ->limit(10)
            ->get();
    }

    protected function countAlerts(array $filters = [], ?string $status = null): int
    {
        if (! $this->hasTable('myhealth_clinical_alerts')) {
            return 0;
        }

        $query = $this->connection()->table('myhealth_clinical_alerts');
        if ($status) {
            $query->where('status', $status);
        }
        return (int) $query->count();
    }

    protected function countBySeverity(array $filters, string $severity): int
    {
        if (! $this->hasTable('myhealth_clinical_alerts')) {
            return 0;
        }

        return (int) $this->connection()->table('myhealth_clinical_alerts')->where('severity', $severity)->count();
    }

    protected function countByType(array $filters, string $type): int
    {
        if (! $this->hasTable('myhealth_clinical_alerts')) {
            return 0;
        }

        return (int) $this->connection()->table('myhealth_clinical_alerts')
            ->where(function ($q) use ($type) {
                $q->where('alert_type', 'like', '%' . $type . '%')
                  ->orWhere('title', 'like', '%' . $type . '%');
            })
            ->count();
    }

    protected function countReminders(array $filters, string $type): int
    {
        if (! $this->hasTable('myhealth_ai_reminders')) {
            return 0;
        }

        return (int) $this->connection()->table('myhealth_ai_reminders')->where('reminder_type', $type)->count();
    }

    protected function applyAlertFilters($query, array $filters): void
    {
        if (!empty($filters['date_from'])) {
            $query->whereDate('a.created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('a.created_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['severity'])) {
            $query->where('a.severity', $filters['severity']);
        }
        if (!empty($filters['status'])) {
            $query->where('a.status', $filters['status']);
        }
        if (!empty($filters['alert_type'])) {
            $query->where('a.alert_type', $filters['alert_type']);
        }
        if (!empty($filters['member_code'])) {
            $query->where('m.member_code', $filters['member_code']);
        }
    }
}
