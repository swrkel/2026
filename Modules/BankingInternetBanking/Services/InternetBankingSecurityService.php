<?php

namespace Modules\BankingInternetBanking\Services;

use Illuminate\Support\Facades\Schema;
use Modules\BankingInternetBanking\Entities\InternetBankingSecurityEvent;

class InternetBankingSecurityService
{
    public function log(string $type, array $payload = [], string $severity = 'info'): void
    {
        if (! Schema::hasTable('bkg_ib_security_events')) {
            return;
        }

        InternetBankingSecurityEvent::query()->create([
            'event_type' => $type,
            'severity' => $severity,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 250),
            'payload' => $payload,
            'created_by' => auth()->id(),
        ]);
    }
}
