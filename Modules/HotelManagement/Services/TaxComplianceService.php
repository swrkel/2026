<?php

namespace Modules\HotelManagement\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class TaxComplianceService
{
    public function dashboard(): array
    {
        $taxes = $this->rows('hm_tax_rules');
        $serviceCharges = $this->rows('hm_service_charge_rules');
        $invoices = $this->rows('hm_tax_invoices');
        $summaries = $this->rows('hm_tax_period_summaries');

        $periodTax = array_sum(array_map(fn($r) => (float)($r->tax_amount ?? 0), $invoices));
        $periodService = array_sum(array_map(fn($r) => (float)($r->service_charge_amount ?? 0), $invoices));
        $gross = array_sum(array_map(fn($r) => (float)($r->gross_amount ?? 0), $invoices));

        return [
            'tax_rules' => $taxes,
            'service_charge_rules' => $serviceCharges,
            'tax_invoices' => $invoices,
            'period_summaries' => $summaries,
            'active_tax_count' => count(array_filter($taxes, fn($r) => (int)($r->is_active ?? 0) === 1)),
            'active_service_count' => count(array_filter($serviceCharges, fn($r) => (int)($r->is_active ?? 0) === 1)),
            'gross_amount' => $gross,
            'tax_amount' => $periodTax,
            'service_charge_amount' => $periodService,
            'notes' => [
                'Tax and service charge rules are stored inside the Hotel Management module and scoped by business/location.',
                'Invoice tax snapshots preserve the applied percentage/rule at posting time, even if rates change later.',
                'Period summaries help reconcile hotel tax, VAT/GST/NBT/service charge and tourism levy totals before posting to Finance.',
            ],
        ];
    }

    public function taxRule(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_tax_rules')) return;
        DB::table('hm_tax_rules')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'tax_code' => strtoupper($data['tax_code'] ?? $this->nextNumber('hm_tax_rules', 'tax_code', 'TAX')),
            'tax_name' => $data['tax_name'] ?? 'Hotel Tax',
            'tax_type' => $data['tax_type'] ?? 'percentage',
            'applies_to' => $data['applies_to'] ?? 'room',
            'rate' => (float)($data['rate'] ?? 0),
            'inclusive_type' => $data['inclusive_type'] ?? 'exclusive',
            'effective_from' => $data['effective_from'] ?? now()->toDateString(),
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function serviceChargeRule(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_service_charge_rules')) return;
        DB::table('hm_service_charge_rules')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'rule_code' => strtoupper($data['rule_code'] ?? $this->nextNumber('hm_service_charge_rules', 'rule_code', 'SC')),
            'rule_name' => $data['rule_name'] ?? 'Service Charge',
            'applies_to' => $data['applies_to'] ?? 'all',
            'rate' => (float)($data['rate'] ?? 0),
            'distribution_method' => $data['distribution_method'] ?? 'manual',
            'effective_from' => $data['effective_from'] ?? now()->toDateString(),
            'effective_to' => $data['effective_to'] ?? null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function taxInvoice(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_tax_invoices')) return;
        $net = (float)($data['net_amount'] ?? 0);
        $taxRate = (float)($data['tax_rate'] ?? 0);
        $serviceRate = (float)($data['service_charge_rate'] ?? 0);
        $taxAmount = round(($net * $taxRate) / 100, 4);
        $serviceAmount = round(($net * $serviceRate) / 100, 4);
        DB::table('hm_tax_invoices')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'invoice_no' => $data['invoice_no'] ?? $this->nextNumber('hm_tax_invoices', 'invoice_no', 'HTAX'),
            'source_type' => $data['source_type'] ?? 'manual',
            'source_id' => $data['source_id'] ?? null,
            'guest_name' => $data['guest_name'] ?? null,
            'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
            'net_amount' => $net,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'service_charge_rate' => $serviceRate,
            'service_charge_amount' => $serviceAmount,
            'gross_amount' => $net + $taxAmount + $serviceAmount,
            'status' => $data['status'] ?? 'posted',
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function periodSummary(array $data, ?int $userId = null): void
    {
        if (!Schema::hasTable('hm_tax_period_summaries')) return;
        DB::table('hm_tax_period_summaries')->insert([
            'business_id' => $this->businessId(),
            'business_location_id' => $this->locationId(),
            'summary_no' => $this->nextNumber('hm_tax_period_summaries', 'summary_no', 'TAXSUM'),
            'period_from' => $data['period_from'] ?? now()->startOfMonth()->toDateString(),
            'period_to' => $data['period_to'] ?? now()->toDateString(),
            'room_revenue' => (float)($data['room_revenue'] ?? 0),
            'fb_revenue' => (float)($data['fb_revenue'] ?? 0),
            'other_revenue' => (float)($data['other_revenue'] ?? 0),
            'tax_amount' => (float)($data['tax_amount'] ?? 0),
            'service_charge_amount' => (float)($data['service_charge_amount'] ?? 0),
            'status' => $data['status'] ?? 'draft',
            'remarks' => $data['remarks'] ?? null,
            'created_by' => $userId,
            'updated_by' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function rows(string $table): array
    {
        if (!Schema::hasTable($table)) return [];
        try {
            return DB::table($table)->where('business_id', $this->businessId())->orderByDesc('id')->limit(100)->get()->all();
        } catch (Throwable $e) { return []; }
    }

    private function nextNumber(string $table, string $column, string $prefix): string
    {
        $last = Schema::hasTable($table) ? DB::table($table)->where('business_id', $this->businessId())->orderByDesc('id')->value($column) : null;
        $n = 1;
        if ($last && preg_match('/(\d+)$/', $last, $m)) $n = ((int)$m[1]) + 1;
        return $prefix . '-' . date('ym') . '-' . str_pad((string)$n, 5, '0', STR_PAD_LEFT);
    }

    private function businessId(): int { return (int)(session('business.id') ?? session('business_id') ?? 1); }
    private function locationId(): ?int { return session('business_location_id') ?? session('location_id') ?? null; }
}
