<?php

namespace Modules\BankingPaymentsHub\Services;

use Illuminate\Support\Facades\Schema;

class PaymentsDashboardService
{
    public function summary(): array
    {
        return [
            'payments' => $this->count('bkg_payment_items'),
            'queue' => $this->count('bkg_payment_queue'),
            'batches' => $this->count('bkg_payment_batches'),
            'routes' => $this->count('bkg_payment_routes'),
            'reconciliations' => $this->count('bkg_payment_reconciliations'),
        ];
    }
    private function count(string $table): int
    {
        return Schema::hasTable($table) ? (int) app('db')->table($table)->count() : 0;
    }
}
