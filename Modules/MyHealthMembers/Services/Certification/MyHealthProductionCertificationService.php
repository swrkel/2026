<?php

namespace Modules\MyHealthMembers\Services\Certification;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MyHealthProductionCertificationService
{
    public function summary(): array
    {
        $sections = $this->sections();
        $total = count($sections);
        $passed = collect($sections)->where('status', 'Ready')->count();

        return [
            'total_sections' => $total,
            'ready_sections' => $passed,
            'pending_sections' => max(0, $total - $passed),
            'readiness_score' => $total > 0 ? round(($passed / $total) * 100, 2) : 0,
            'sections' => $sections,
        ];
    }

    public function sections(): array
    {
        return [
            $this->section('Standalone Architecture', 'Core module folders, services, routes and views are present.', $this->hasCoreFolders()),
            $this->section('Public Registration', 'Public member registration and passcode workflow routes are available.', Route::has('myhealth-register') || Route::has('myhealth.public.register.store')),
            $this->section('Member Management', 'Member register, profile and portal foundations are available.', Route::has('myhealth.members.index') || Route::has('myhealth.portal.dashboard')),
            $this->section('Doctor Portal', 'Doctor and consultation route foundations are available.', Route::has('myhealth.doctors.index') || Route::has('myhealth.doctor.records.index')),
            $this->section('Hospital / HIS', 'Hospital dashboard, appointments and queue foundations are available.', Route::has('myhealth.hospital.dashboard')),
            $this->section('Nursing', 'Nursing station and vital sign foundations are available.', Route::has('myhealth.nursing.dashboard')),
            $this->section('Laboratory', 'Laboratory enterprise workflow routes are available.', Route::has('myhealth.laboratory.dashboard')),
            $this->section('Radiology', 'Radiology request and report foundations are available.', Route::has('myhealth.radiology.dashboard')),
            $this->section('Operation Theatre', 'Operation theatre scheduling and records foundations are available.', Route::has('myhealth.operation_theatre.dashboard')),
            $this->section('Vaccination', 'Vaccination register and schedule foundations are available.', Route::has('myhealth.vaccination.dashboard')),
            $this->section('Analytics', 'Executive and operational analytics pages are available.', Route::has('myhealth.analytics.dashboard')),
            $this->section('AI Clinical Assistant', 'Clinical alert and safety review foundations are available.', Route::has('myhealth.ai_clinical.dashboard')),
            $this->section('Disaster Recovery', 'Backup, restore and health monitoring foundations are available.', Route::has('myhealth.disaster_recovery.dashboard')),
            $this->section('Reports Inside Module', 'My Health reports remain under the MyHealthMembers module.', Route::has('myhealth.reports.index')),
            $this->section('Settings Inside Module', 'My Health settings are available inside the module.', Route::has('myhealth.settings.index')),
        ];
    }

    public function standaloneChecklist(): array
    {
        return [
            ['area' => 'Controllers', 'target' => 'Feature-based controllers inside Modules/MyHealthMembers/Http/Controllers', 'status' => 'Ready'],
            ['area' => 'Entities', 'target' => 'MyHealth-prefixed entities inside the module', 'status' => 'Ready'],
            ['area' => 'Services', 'target' => 'Business logic separated into module services', 'status' => 'Ready'],
            ['area' => 'Routes', 'target' => 'Module-owned route files loaded by the module provider', 'status' => 'Ready'],
            ['area' => 'Views', 'target' => 'Blade views located under module Resources/views', 'status' => 'Ready'],
            ['area' => 'Reports', 'target' => 'All My Health reports remain under the My Health menu', 'status' => 'Ready'],
            ['area' => 'Permissions', 'target' => 'MyHealth permissions isolated in module config', 'status' => 'Ready'],
            ['area' => 'Migrations', 'target' => 'Database changes stored in module migrations', 'status' => 'Ready'],
            ['area' => 'Documentation', 'target' => 'Release notes and audit documents stored inside module Documentation', 'status' => 'Ready'],
        ];
    }

    public function securityChecklist(): array
    {
        return [
            ['item' => 'Authentication', 'status' => 'Review before production', 'note' => 'Verify ERP auth and public member login flows.'],
            ['item' => 'Authorization', 'status' => 'Review before production', 'note' => 'Assign My Health permissions to each role.'],
            ['item' => 'Consent / OTP', 'status' => 'Review before production', 'note' => 'Confirm expiry time and SMS/email delivery.'],
            ['item' => 'QR Access', 'status' => 'Review before production', 'note' => 'Confirm QR access expiry and audit trail.'],
            ['item' => 'Audit Logs', 'status' => 'Review before production', 'note' => 'Confirm all sensitive access is logged.'],
            ['item' => 'File Uploads', 'status' => 'Review before production', 'note' => 'Confirm allowed file types and storage permissions.'],
            ['item' => 'Backups', 'status' => 'Review before production', 'note' => 'Confirm backup destination and restore test.'],
        ];
    }

    public function performanceChecklist(): array
    {
        return [
            ['item' => 'Dashboard queries', 'status' => 'Ready for load test'],
            ['item' => 'Member search', 'status' => 'Ready for index review'],
            ['item' => 'Reports', 'status' => 'Ready for export/load test'],
            ['item' => 'Clinical timeline', 'status' => 'Ready for pagination review'],
            ['item' => 'Documents and images', 'status' => 'Ready for storage review'],
            ['item' => 'Analytics', 'status' => 'Ready for summary table review'],
        ];
    }

    public function workflowChecklist(): array
    {
        return [
            'Register a My Health member and confirm member code/passcode are shown.',
            'Login as member and confirm member portal pages open.',
            'Search member from back-office member register.',
            'Create doctor consultation, diagnosis and prescription.',
            'Create pharmacy dispense from a prescription.',
            'Create lab request, collect sample and enter result.',
            'Create radiology request and release radiology report.',
            'Create appointment/token through HIS reception.',
            'Record nursing vitals and nursing notes.',
            'Schedule operation theatre case and complete post-op note.',
            'Record vaccination and print certificate.',
            'Submit insurance claim and billing invoice/payment.',
            'Approve business access through OTP/consent.',
            'Review reports, analytics and audit trail.',
            'Create backup record and verify restore test log.',
        ];
    }

    protected function section(string $name, string $description, bool $ready): array
    {
        return [
            'name' => $name,
            'description' => $description,
            'status' => $ready ? 'Ready' : 'Needs Review',
        ];
    }

    protected function hasCoreFolders(): bool
    {
        $base = module_path('MyHealthMembers');
        foreach (['Http/Controllers', 'Entities', 'Services', 'Routes', 'Resources/views', 'Database/Migrations', 'Config'] as $folder) {
            if (! is_dir($base . DIRECTORY_SEPARATOR . $folder)) {
                return false;
            }
        }
        return true;
    }
}
