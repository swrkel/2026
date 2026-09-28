<?php

namespace Modules\AutoService\Http\Controllers;

class DashboardController extends AutoServiceBaseController
{
    /**
     * Auto Service dashboard.
     * Uses the active tenant connection resolved by AutoServiceBaseController.
     */
    public function index()
    {
        $missing = $this->missingAutoServiceTenantTables();
        if (!empty($missing)) {
            return view('autoservice::dashboard.setup_required', [
                'missing_tables' => $missing,
                'required_tables' => $this->autoServiceRequiredTenantTables,
                'autoservice_hide_nav' => true,
                'checked_connection' => $this->autoServiceTenantConnection(),
                'checked_database' => $this->autoServiceTenantDatabaseName(),
            ]);
        }

        $businessId = $this->businessId();
        $db = $this->tenantDb();

        $jobQ = $db->table('auto_service_jobs');
        if ($businessId) {
            $jobQ->where('business_id', $businessId);
        }

        $vehicleQ = $db->table('auto_service_vehicles');
        if ($businessId) {
            $vehicleQ->where('business_id', $businessId);
        }

        $reminderQ = $db->table('auto_service_reminders');
        if ($businessId) {
            $reminderQ->where('business_id', $businessId);
        }

        $data = [
            'jobs_today' => (clone $jobQ)->whereDate('job_date', date('Y-m-d'))->count(),
            'open_jobs' => (clone $jobQ)->whereNotIn('status', ['delivered', 'cancelled'])->count(),
            'vehicles' => $vehicleQ->count(),
            'due_reminders' => $reminderQ->where('status', 'pending')->whereDate('send_on', '<=', date('Y-m-d'))->count(),
            'recent_jobs' => (clone $jobQ)->orderByDesc('id')->limit(10)->get(),
        ];

        return view('autoservice::dashboard.index', $data);
    }
}
