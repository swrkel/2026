<?php

namespace Modules\MyHealthMembers\Services\Reports;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\MyHealthMembers\Entities\MyHealthBillingInvoice;
use Modules\MyHealthMembers\Entities\MyHealthBillingPayment;
use Modules\MyHealthMembers\Entities\MyHealthDiagnosis;
use Modules\MyHealthMembers\Entities\MyHealthDispense;
use Modules\MyHealthMembers\Entities\MyHealthDoctor;
use Modules\MyHealthMembers\Entities\MyHealthInsuranceClaim;
use Modules\MyHealthMembers\Entities\MyHealthLabRequest;
use Modules\MyHealthMembers\Entities\MyHealthMedicalHistory;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthPrescription;
use Modules\MyHealthMembers\Entities\MyHealthTelemedicineAppointment;

class MyHealthReportService
{
    public function dateRange(?string $startDate, ?string $endDate): array
    {
        $start = $startDate ? Carbon::parse($startDate)->startOfDay() : now()->subDays(30)->startOfDay();
        $end = $endDate ? Carbon::parse($endDate)->endOfDay() : now()->endOfDay();
        return [$start, $end];
    }

    public function dashboard(array $filters): array
    {
        [$start, $end] = $this->dateRange($filters['start_date'] ?? null, $filters['end_date'] ?? null);
        return [
            'members' => MyHealthMember::count(),
            'prescriptions' => MyHealthPrescription::whereBetween('created_at', [$start, $end])->count(),
            'labs' => MyHealthLabRequest::whereBetween('created_at', [$start, $end])->count(),
            'dispenses' => MyHealthDispense::whereBetween('created_at', [$start, $end])->count(),
            'claims' => MyHealthInsuranceClaim::whereBetween('created_at', [$start, $end])->count(),
            'telemedicine' => MyHealthTelemedicineAppointment::whereBetween('created_at', [$start, $end])->count(),
            'revenue' => MyHealthBillingPayment::whereBetween('created_at', [$start, $end])->sum('amount'),
        ];
    }

    public function patientHistory(array $filters) { return $this->applyDate(MyHealthMedicalHistory::with('member'), $filters)->latest()->limit(500)->get(); }
    public function prescriptions(array $filters) { return $this->applyDate(MyHealthPrescription::query(), $filters)->latest()->limit(500)->get(); }
    public function labs(array $filters) { return $this->applyDate(MyHealthLabRequest::with('result'), $filters)->latest()->limit(500)->get(); }
    public function dispenses(array $filters) { return $this->applyDate(MyHealthDispense::with(['member','items.medicine']), $filters)->latest()->limit(500)->get(); }
    public function claims(array $filters) { return $this->applyDate(MyHealthInsuranceClaim::with(['member','policy']), $filters)->latest()->limit(500)->get(); }
    public function telemedicine(array $filters) { return $this->applyDate(MyHealthTelemedicineAppointment::with(['member','doctor']), $filters)->latest()->limit(500)->get(); }
    public function revenue(array $filters) { return $this->applyDate(MyHealthBillingInvoice::with(['member','payments']), $filters)->latest()->limit(500)->get(); }
    public function doctorPerformance(array $filters) { return MyHealthDoctor::withCount(['schedules'])->latest()->limit(500)->get(); }

    public function exportData(string $report, array $filters): array
    {
        return match ($report) {
            'patient-history' => [['Date', 'Member ID', 'Type', 'Details'], $this->mapRows($this->patientHistory($filters), fn($r) => [$this->date($r->created_at), $r->member_id, $r->history_type ?? '', $r->details ?? '']), 'Patient History Report'],
            'prescriptions' => [['Date', 'Member ID', 'Doctor/User', 'Prescription', 'Instructions'], $this->mapRows($this->prescriptions($filters), fn($r) => [$this->date($r->prescription_date ?? $r->created_at), $r->member_id, $r->doctor_user_id, $r->prescription_details ?? '', $r->instructions ?? '']), 'Prescription Report'],
            'labs' => [['Date', 'Member ID', 'Request No', 'Test', 'Status'], $this->mapRows($this->labs($filters), fn($r) => [$this->date($r->created_at), $r->member_id, $r->request_no ?? '', $r->test_name ?? '', $r->status ?? '']), 'Lab Report'],
            'dispenses' => [['Date', 'Member ID', 'Dispense No', 'Status', 'Total'], $this->mapRows($this->dispenses($filters), fn($r) => [$this->date($r->dispense_date ?? $r->created_at), $r->member_id, $r->dispense_no ?? '', $r->status ?? '', $r->total_amount ?? 0]), 'Medicine Dispense Report'],
            'claims' => [['Date', 'Member ID', 'Claim No', 'Status', 'Claim Amount'], $this->mapRows($this->claims($filters), fn($r) => [$this->date($r->created_at), $r->member_id, $r->claim_no ?? '', $r->status ?? '', $r->claim_amount ?? 0]), 'Insurance Claim Report'],
            'telemedicine' => [['Date', 'Member ID', 'Doctor ID', 'Status', 'Meeting URL'], $this->mapRows($this->telemedicine($filters), fn($r) => [$this->date($r->appointment_date ?? $r->created_at), $r->member_id, $r->doctor_id, $r->status ?? '', $r->meeting_url ?? '']), 'Telemedicine Report'],
            'revenue' => [['Date', 'Member ID', 'Invoice No', 'Status', 'Total'], $this->mapRows($this->revenue($filters), fn($r) => [$this->date($r->invoice_date ?? $r->created_at), $r->member_id, $r->invoice_no ?? '', $r->status ?? '', $r->total_amount ?? 0]), 'Revenue Report'],
            'doctor-performance' => [['Doctor ID', 'Name', 'Schedules', 'Status'], $this->mapRows($this->doctorPerformance($filters), fn($r) => [$r->id, $r->name ?? '', $r->schedules_count ?? 0, $r->status ?? '']), 'Doctor Performance Report'],
            default => abort(404),
        };
    }

    private function applyDate(Builder $query, array $filters): Builder
    {
        [$start, $end] = $this->dateRange($filters['start_date'] ?? null, $filters['end_date'] ?? null);
        return $query->whereBetween('created_at', [$start, $end]);
    }

    private function mapRows($rows, callable $callback): array
    {
        return collect($rows)->map($callback)->toArray();
    }

    private function date($value): string
    {
        return $value ? Carbon::parse($value)->format('Y-m-d H:i') : '';
    }
}
