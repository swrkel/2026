<?php

namespace Modules\MyHealthMembers\Services\Admin;

use Illuminate\Support\Facades\DB;

class MyHealthAdministrationService
{
    public function dashboard(): array
    {
        return [
            'total_members' => $this->count('myhealth_members'),
            'active_members' => $this->count('myhealth_members', ['status' => 'active']),
            'total_doctors' => $this->count('myhealth_doctors'),
            'total_consultations' => $this->count('myhealth_consultations'),
            'pending_consents' => $this->count('myhealth_member_consents', ['status' => 'pending']),
            'pending_lab_requests' => $this->count('myhealth_lab_requests', ['status' => 'pending']),
            'insurance_claims' => $this->count('myhealth_insurance_claims'),
            'pending_notifications' => $this->count('myhealth_notifications', ['status' => 'pending']),
            'audit_alerts' => $this->count('myhealth_audit_logs'),
        ];
    }

    public function doctors()
    {
        if (! $this->tableExists('myhealth_doctors')) {
            return collect();
        }

        return DB::table('myhealth_doctors')
            ->orderBy('name')
            ->paginate(25);
    }

    public function businesses()
    {
        if (! $this->tableExists('business')) {
            return collect();
        }

        return DB::table('business')
            ->select('id', 'name', 'email', 'mobile', 'created_at')
            ->orderBy('name')
            ->paginate(25);
    }

    public function settingsGroups(): array
    {
        return [
            'registration' => 'Registration Settings',
            'member_code' => 'Member Code Settings',
            'passcode' => 'Passcode Settings',
            'otp' => 'OTP Settings',
            'qr' => 'QR Settings',
            'sms' => 'SMS Settings',
            'email' => 'Email Settings',
            'notification' => 'Notification Settings',
            'telemedicine' => 'Telemedicine Settings',
            'doctor' => 'Doctor Settings',
            'pharmacy' => 'Pharmacy Settings',
            'laboratory' => 'Laboratory Settings',
            'insurance' => 'Insurance Settings',
            'billing' => 'Billing Settings',
        ];
    }

    protected function count(string $table, array $where = []): int
    {
        if (! $this->tableExists($table)) {
            return 0;
        }

        $query = DB::table($table);
        foreach ($where as $column => $value) {
            $query->where($column, $value);
        }
        return (int) $query->count();
    }

    protected function tableExists(string $table): bool
    {
        try {
            return DB::getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
