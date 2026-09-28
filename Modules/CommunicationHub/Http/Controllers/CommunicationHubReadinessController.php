<?php

namespace Modules\CommunicationHub\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class CommunicationHubReadinessController extends Controller
{
    public function index(Request $request)
    {
        $requiredTables = [
            'communication_hub_messages',
            'communication_hub_templates',
            'communication_hub_providers',
            'communication_hub_otps',
            'communication_hub_campaigns',
            'communication_hub_audit_logs',
            'communication_hub_delivery_events',
            'communication_hub_api_clients',
            'communication_hub_api_request_logs',
            'communication_hub_sms_wallets',
            'communication_hub_sms_credit_transactions',
            'communication_hub_whatsapp_profiles',
            'communication_hub_push_devices',
            'communication_hub_in_app_notifications',
            'communication_hub_chat_conversations',
            'communication_hub_internal_messages',
            'communication_hub_automation_rules',
            'communication_hub_workflow_rules',
        ];

        $requiredRoutes = [
            'communicationhub.dashboard',
            'communicationhub.commercial.sms_dashboard',
            'communicationhub.commercial.send_sms',
            'communicationhub.otp.index',
            'communicationhub.email.dashboard',
            'communicationhub.email.compose',
            'communicationhub.email.send',
            'communicationhub.email.bulk',
            'communicationhub.email.bulk.store',
            'communicationhub.email.queue',
            'communicationhub.email.retry',
            'communicationhub.commercial.whatsapp_dashboard',
            'communicationhub.commercial.push_dashboard',
            'communicationhub.commercial.in_app_dashboard',
            'communicationhub.commercial.chat_dashboard',
            'communicationhub.commercial.automation_dashboard',
            'communicationhub.commercial.workflow_dashboard',
            'communicationhub.commercial.analytics_dashboard',
            'communicationhub.commercial.api_gateway_dashboard',
            'communicationhub.readiness.index',
        ];

        // Include every configured sidebar route so future menu/route drift is
        // reported by the Readiness Check before users encounter a 404 page.
        $menuRoutes = collect(config('communicationhub_menu.items', []))
            ->pluck('route')
            ->filter(fn ($route) => is_string($route) && $route !== '')
            ->values()
            ->all();

        $requiredRoutes = array_values(array_unique(array_merge($requiredRoutes, $menuRoutes)));

        $tableChecks = [];
        foreach ($requiredTables as $table) {
            $tableChecks[] = [
                'name' => $table,
                'status' => $this->tableExists($table),
            ];
        }

        $routeChecks = [];
        foreach ($requiredRoutes as $route) {
            $routeChecks[] = [
                'name' => $route,
                'status' => Route::has($route),
            ];
        }

        $configPermissions = config('communicationhub.permissions', []);
        if (empty($configPermissions)) {
            $permissionFile = module_path('CommunicationHub', 'Config/permissions.php');
            $configPermissions = is_file($permissionFile) ? include $permissionFile : [];
        }

        return view('communicationhub::readiness.index', [
            'tableChecks' => $tableChecks,
            'routeChecks' => $routeChecks,
            'permissionCount' => is_array($configPermissions) ? count($configPermissions) : 0,
            'checkedAt' => now(),
        ]);
    }

    private function tableExists(string $table): bool
    {
        try {
            $database = DB::getDatabaseName();
            $result = DB::select(
                'SELECT COUNT(*) AS aggregate FROM information_schema.tables WHERE table_schema = ? AND table_name = ?',
                [$database, $table]
            );
            return (int)($result[0]->aggregate ?? 0) > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
