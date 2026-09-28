<?php

namespace Modules\ManagementReport\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\ManagementReport\Services\Installation\TenantSchemaInstaller;
use Modules\ManagementReport\Support\TenantConnection;
use Symfony\Component\HttpFoundation\Response;

class EnsureManagementReportTables
{
    public function handle(Request $request, Closure $next): Response
    {
        $required = [
            'mgmt_report_templates',
            'mgmt_report_runs',
            'mgmt_report_run_sections',
            'mgmt_report_shares',
            'mgmt_report_share_recipients',
            'mgmt_report_settings',
            'mgmt_report_review_statuses',
        ];

        $missing = $this->missingTables($required);

        // Shared/central-system businesses did not participate in the tenant-only
        // installer introduced by older module versions. For a valid current
        // business database (including older central deployments whose hostname
        // is not listed in tenancy.central_domains), safely create only this
        // module's own missing mgmt_* tables on first access. Existing ERP
        // tables/data are untouched.
        if (
            $missing !== []
            && TenantConnection::isCentralBusinessDatabase()
            && (bool) config('managementreport.auto_install_central_business_tables', true)
        ) {
            try {
                app(TenantSchemaInstaller::class)->install();
                $missing = $this->missingTables($required);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if ($missing !== []) {
            $mode = TenantConnection::isCentralBusinessDatabase()
                ? 'central/shared business database'
                : 'tenant database';

            abort(503, sprintf(
                'Management Report is not installed in the %s "%s". Missing tables: %s. Run the Management Report installer/SQL for this database.',
                $mode,
                TenantConnection::databaseName(),
                implode(', ', $missing)
            ));
        }

        return $next($request);
    }

    private function missingTables(array $required): array
    {
        $schema = TenantConnection::schema();

        return array_values(array_filter($required, static function ($table) use ($schema) {
            return !$schema->hasTable($table);
        }));
    }
}
