<?php

namespace Modules\MyHealthMembers\Services\Portal;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\MyHealthMembers\Entities\MyHealthMember;
use Modules\MyHealthMembers\Entities\MyHealthMemberLogin;

class MyHealthMemberPortalService
{
    public function resolveMember($user = null): ?MyHealthMember
    {
        $sessionMemberId = session('myhealth_member_id');
        if (! empty($sessionMemberId)) {
            return MyHealthMember::find($sessionMemberId);
        }

        if (!$user) {
            return null;
        }

        if (isset($user->myhealth_member_id)) {
            return MyHealthMember::find($user->myhealth_member_id);
        }

        if (!empty($user->email)) {
            return MyHealthMember::where('email', $user->email)->first();
        }

        if (!empty($user->mobile)) {
            return MyHealthMember::where('mobile', $user->mobile)->first();
        }

        return null;
    }

    public function dashboard($user = null): array
    {
        $member = $this->resolveMember($user);

        return [
            'member' => $member,
            'consultation_count' => $this->countForMember('myhealth_consultations', $member),
            'prescription_count' => $this->countForMember('myhealth_prescriptions', $member),
            'document_count' => $this->countForMember('myhealth_documents', $member),
            'lab_result_count' => $this->countForMember('myhealth_lab_results', $member),
            'radiology_count' => $this->countForMember('myhealth_radiology_reports', $member),
            'vaccination_count' => $this->countForMember('myhealth_vaccination_records', $member),
            'invoice_count' => $this->countForMember('myhealth_billing_invoices', $member),
            'appointments' => $this->latestForMember('myhealth_appointments', $member, 5),
            'recent_prescriptions' => $this->latestForMember('myhealth_prescriptions', $member, 5),
            'recent_labs' => $this->latestForMember('myhealth_lab_results', $member, 5),
            'recent_radiology' => $this->latestForMember('myhealth_radiology_reports', $member, 5),
            'notifications' => $this->latestForMember('myhealth_notifications', $member, 5),
            'timeline' => $this->timelineItems($member, 8),
            'health_summary' => $this->healthSummary($member),
            'portal_security' => $this->portalSecuritySummary($member),
        ];
    }

    public function profile($user = null): array
    {
        return ['member' => $this->resolveMember($user), 'health_summary' => $this->healthSummary($this->resolveMember($user))];
    }

    public function history($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'history' => $this->paginateForMember('myhealth_medical_histories', $member),
        ];
    }

    public function prescriptions($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'prescriptions' => $this->paginateForMember('myhealth_prescriptions', $member),
        ];
    }

    public function documents($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'documents' => $this->paginateForMember('myhealth_documents', $member),
        ];
    }

    public function downloadDocument($user = null, int $documentId): array
    {
        $member = $this->resolveMember($user);
        if (! $member || ! $this->hasTable('myhealth_documents')) {
            return ['success' => false, 'message' => 'Document not found.'];
        }

        $document = DB::table('myhealth_documents')
            ->where('member_id', $member->id)
            ->where('id', $documentId)
            ->first();

        if (! $document || empty($document->file_path)) {
            return ['success' => false, 'message' => 'Document not found or access denied.'];
        }

        $path = (string) $document->file_path;
        $absolutePath = $path;

        if (! file_exists($absolutePath)) {
            $absolutePath = storage_path('app/' . ltrim($path, '/'));
        }

        if (! file_exists($absolutePath)) {
            try {
                if (Storage::exists($path)) {
                    $absolutePath = Storage::path($path);
                }
            } catch (\Throwable $e) {
                // Fall through to not found.
            }
        }

        if (! file_exists($absolutePath)) {
            return ['success' => false, 'message' => 'Document file is not available on the server.'];
        }

        $this->auditMemberAction($member->id, 'document_download', 'Downloaded document ID ' . $documentId);

        return [
            'success' => true,
            'path' => $absolutePath,
            'filename' => $document->title ?? basename($absolutePath),
        ];
    }

    public function labs($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'lab_results' => $this->paginateForMember('myhealth_lab_results', $member),
        ];
    }

    public function radiology($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'radiology_reports' => $this->paginateForMember('myhealth_radiology_reports', $member),
        ];
    }

    public function vaccinations($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'vaccinations' => $this->paginateForMember('myhealth_vaccination_records', $member),
        ];
    }

    public function billing($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'invoices' => $this->paginateForMember('myhealth_billing_invoices', $member),
        ];
    }

    public function appointments($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'appointments' => $this->paginateForMember('myhealth_appointments', $member),
        ];
    }

    public function requestAppointment($user = null, array $payload = []): array
    {
        $member = $this->resolveMember($user);
        if (! $member) {
            return ['success' => false, 'message' => 'Member session not found. Please login again.'];
        }

        if (! $this->hasTable('myhealth_appointments')) {
            return ['success' => false, 'message' => 'Appointment table is not available yet.'];
        }

        $appointmentNo = 'APT-' . date('ymdHis') . '-' . $member->id;
        $row = [
            'business_id' => $member->business_id ?? $member->registered_business_id ?? null,
            'appointment_no' => $appointmentNo,
            'member_id' => $member->id,
            'appointment_date' => $payload['appointment_date'] ?? now()->toDateString(),
            'appointment_time' => $payload['appointment_time'] ?? null,
            'visit_type' => 'portal_request',
            'status' => 'requested',
            'reason' => $payload['reason'] ?? null,
            'notes' => 'Requested from My Health Member Portal',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $columns = $this->columns('myhealth_appointments');
        $row = array_intersect_key($row, array_flip($columns));
        DB::table('myhealth_appointments')->insert($row);

        $this->auditMemberAction($member->id, 'appointment_request', 'Appointment requested from portal');
        $this->queueNotification($member, 'Appointment request submitted', 'Your My Health appointment request was submitted successfully.');

        return ['success' => true, 'message' => 'Appointment request submitted successfully.'];
    }

    public function timeline($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'timeline' => $this->timelineItems($member, 50),
        ];
    }

    public function notifications($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'notifications' => $this->paginateForMember('myhealth_notifications', $member),
        ];
    }

    public function settings($user = null): array
    {
        return [
            'member' => $member = $this->resolveMember($user),
            'portal_security' => $this->portalSecuritySummary($member),
        ];
    }

    public function changePasscode($user = null, array $payload = []): array
    {
        $member = $this->resolveMember($user);
        if (! $member) {
            return ['success' => false, 'message' => 'Member session not found. Please login again.'];
        }

        $login = MyHealthMemberLogin::where('member_id', $member->id)->first();
        if (! $login) {
            return ['success' => false, 'message' => 'Login record not found.'];
        }

        $current = trim((string) ($payload['current_passcode'] ?? ''));
        $new = trim((string) ($payload['new_passcode'] ?? ''));

        $validCurrent = hash_equals((string) $login->login_code, $current);
        if (! $validCurrent && ! empty($login->password)) {
            try {
                $validCurrent = Hash::check($current, (string) $login->password);
            } catch (\Throwable $e) {
                $validCurrent = false;
            }
        }

        if (! $validCurrent) {
            return ['success' => false, 'message' => 'Current passcode is incorrect.'];
        }

        if (MyHealthMemberLogin::where('login_code', $new)->where('member_id', '!=', $member->id)->exists()) {
            return ['success' => false, 'message' => 'This passcode is already in use. Please choose another.'];
        }

        // Keep login_code as the current production passcode field for backward compatibility.
        $login->update([
            'login_code' => $new,
            'password' => Hash::make($new),
        ]);

        $this->auditMemberAction($member->id, 'passcode_changed', 'Member changed portal passcode');

        return ['success' => true, 'message' => 'Passcode changed successfully. Please keep it secure and confidential.'];
    }

    protected function countForMember(string $table, ?MyHealthMember $member): int
    {
        if (! $member || ! $this->hasTable($table)) {
            return 0;
        }

        return DB::table($table)->where('member_id', $member->id)->count();
    }

    protected function latestForMember(string $table, ?MyHealthMember $member, int $limit = 10)
    {
        if (! $member || ! $this->hasTable($table)) {
            return collect();
        }

        return DB::table($table)->where('member_id', $member->id)->latest('id')->limit($limit)->get();
    }

    protected function paginateForMember(string $table, ?MyHealthMember $member, int $perPage = 25)
    {
        if (! $member || ! $this->hasTable($table)) {
            return collect();
        }

        return DB::table($table)->where('member_id', $member->id)->latest('id')->paginate($perPage);
    }

    protected function healthSummary(?MyHealthMember $member): array
    {
        if (! $member) {
            return [];
        }

        return [
            'blood_group' => $member->blood_group ?? null,
            'allergies' => $this->countForMember('myhealth_allergies', $member),
            'chronic_conditions' => $this->countForMember('myhealth_chronic_conditions', $member),
            'last_consultation' => optional($this->latestForMember('myhealth_consultations', $member, 1)->first())->created_at,
            'emergency_contact' => $member->emergency_contact_name ?? null,
            'emergency_mobile' => $member->emergency_contact_mobile ?? null,
        ];
    }

    protected function portalSecuritySummary(?MyHealthMember $member): array
    {
        return [
            'member_id' => $member->id ?? null,
            'member_code' => $member->myhealth_code ?? null,
            'logged_in_at' => session('myhealth_member_logged_in_at'),
            'last_activity_at' => session('myhealth_member_last_activity_at'),
            'access_scope' => 'Own records only',
            'erp_access' => 'Disabled',
        ];
    }

    protected function timelineItems(?MyHealthMember $member, int $limit = 20)
    {
        if (! $member) {
            return collect();
        }

        $sources = [
            ['table' => 'myhealth_consultations', 'type' => 'Consultation', 'icon' => 'stethoscope'],
            ['table' => 'myhealth_diagnoses', 'type' => 'Diagnosis', 'icon' => 'heartbeat'],
            ['table' => 'myhealth_prescriptions', 'type' => 'Prescription', 'icon' => 'medkit'],
            ['table' => 'myhealth_lab_results', 'type' => 'Laboratory Result', 'icon' => 'flask'],
            ['table' => 'myhealth_radiology_reports', 'type' => 'Radiology Report', 'icon' => 'file-image-o'],
            ['table' => 'myhealth_vaccination_records', 'type' => 'Vaccination', 'icon' => 'shield'],
            ['table' => 'myhealth_appointments', 'type' => 'Appointment', 'icon' => 'calendar'],
            ['table' => 'myhealth_documents', 'type' => 'Document', 'icon' => 'folder-open'],
        ];

        $items = collect();
        foreach ($sources as $source) {
            if (! $this->hasTable($source['table'])) {
                continue;
            }
            $dateColumn = $this->bestDateColumn($source['table']);
            $rows = DB::table($source['table'])
                ->where('member_id', $member->id)
                ->orderByDesc($dateColumn)
                ->limit($limit)
                ->get();

            foreach ($rows as $row) {
                $items->push((object) [
                    'type' => $source['type'],
                    'icon' => $source['icon'],
                    'date' => $row->{$dateColumn} ?? null,
                    'title' => $this->timelineTitle($row, $source['type']),
                    'status' => $row->status ?? null,
                    'notes' => $row->notes ?? $row->remarks ?? $row->description ?? null,
                ]);
            }
        }

        return $items->sortByDesc('date')->take($limit)->values();
    }

    protected function timelineTitle($row, string $fallback): string
    {
        foreach (['title', 'diagnosis', 'medicine_name', 'test_name', 'report_title', 'vaccine_name', 'document_name', 'appointment_no', 'consultation_no', 'invoice_no', 'request_no'] as $field) {
            if (! empty($row->{$field})) {
                return (string) $row->{$field};
            }
        }

        return $fallback;
    }

    protected function bestDateColumn(string $table): string
    {
        $columns = $this->columns($table);
        foreach (['appointment_date', 'result_date', 'report_date', 'vaccination_date', 'date_given', 'consultation_date', 'prescription_date', 'invoice_date', 'created_at', 'id'] as $column) {
            if (in_array($column, $columns, true)) {
                return $column;
            }
        }

        return 'id';
    }

    protected function queueNotification(MyHealthMember $member, string $subject, string $message): void
    {
        try {
            if (class_exists('Modules\\CommunicationHub\\Services\\CommunicationHubService')) {
                if (! empty($member->email)) {
                    app('Modules\\CommunicationHub\\Services\\CommunicationHubService')->queueMessage([
                        'channel' => 'email',
                        'recipient' => $member->email,
                        'subject' => $subject,
                        'message' => $message,
                        'priority' => 'normal',
                        'payload' => ['module' => 'MyHealthMembers', 'member_id' => $member->id],
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::warning('My Health portal notification queue failed: ' . $e->getMessage());
        }
    }

    protected function auditMemberAction(int $memberId, string $action, string $notes): void
    {
        try {
            if (! Schema::hasTable('myhealth_access_logs')) {
                return;
            }

            DB::table('myhealth_access_logs')->insert([
                'member_id' => $memberId,
                'business_id' => (int) (session('business.id') ?? 0),
                'user_id' => null,
                'section' => 'member_portal',
                'action' => $action,
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'accessed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('My Health portal audit failed: ' . $e->getMessage());
        }
    }

    protected function columns(string $table): array
    {
        try {
            return Schema::getColumnListing($table);
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function hasTable(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
