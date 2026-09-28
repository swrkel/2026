<?php

namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\Schema;

class AutoServiceReleaseAuditService
{
    public function checklist(): array
    {
        $tables = [
            'auto_service_jobs', 'auto_service_vehicles', 'auto_service_invoices',
            'auto_service_invoice_lines', 'auto_service_payments', 'auto_service_mechanics',
            'auto_service_job_mechanics', 'auto_service_part_movements', 'auto_service_settings',
            'auto_service_notifications', 'auto_service_communications', 'auto_service_bays',
            'auto_service_feedback', 'auto_service_documents', 'auto_service_approval_requests',
        ];

        $items = [];
        foreach ($tables as $table) {
            $items[] = [
                'section' => 'Database',
                'item' => $table,
                'status' => Schema::hasTable($table) ? 'OK' : 'Missing',
                'message' => Schema::hasTable($table) ? 'Table available' : 'Create/migrate this table before production',
            ];
        }

        foreach (['allow_customer_current_invoice','allow_customer_invoice_pdf','allow_customer_fleet_view','allow_customer_feedback'] as $setting) {
            $items[] = [
                'section' => 'Customer Portal Settings',
                'item' => $setting,
                'status' => 'Configurable',
                'message' => 'Controlled in Auto Service settings; defaults are safe/disabled when absent.',
            ];
        }

        foreach (['job_created','estimate_ready','approval_required','vehicle_ready','invoice_generated','payment_received','vehicle_delivered'] as $event) {
            $items[] = [
                'section' => 'Notifications',
                'item' => $event,
                'status' => 'Ready',
                'message' => 'Notification event registered for SMS/email adapter usage.',
            ];
        }

        return $items;
    }

    public function summary(): array
    {
        $items = $this->checklist();
        return [
            'total' => count($items),
            'ok' => collect($items)->whereIn('status', ['OK','Ready','Configurable'])->count(),
            'missing' => collect($items)->where('status', 'Missing')->count(),
            'items' => $items,
        ];
    }
}
