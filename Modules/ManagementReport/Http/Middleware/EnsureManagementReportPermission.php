<?php
namespace Modules\ManagementReport\Http\Middleware;
use Modules\ManagementReport\Support\TenantConnection;

use Closure;

class EnsureManagementReportPermission
{
    protected $routePermissions = [
        'managementreport.dashboard' => 'management_report.view',
        'managementreport.daily.index' => 'management_report.generate',
        'managementreport.daily.preview' => 'management_report.generate',
        'managementreport.daily.generate' => 'management_report.generate',
        'managementreport.daily.print' => 'management_report.print',
        'managementreport.daily.pdf' => 'management_report.download_pdf',
        'managementreport.saved.index' => 'management_report.view_saved',
        'managementreport.saved.show' => 'management_report.view_saved',
        'managementreport.saved.destroy' => 'management_report.delete_saved',
        'managementreport.reviews.store' => 'management_report.review',
        'managementreport.shares.index' => 'management_report.view_delivery_history',
        'managementreport.shares.revoke' => 'management_report.revoke_share',
        'managementreport.settings.index' => 'management_report.settings',
        'managementreport.settings.update' => 'management_report.settings',
    ];

    public function handle($request, Closure $next)
    {
        $routeName = optional($request->route())->getName();
        $permission = $this->routePermissions[$routeName] ?? null;
        if ($routeName === 'managementreport.daily.share') {
            $channel = strtolower((string) $request->input('channel'));
            $permission = in_array($channel, ['sms', 'email', 'whatsapp'], true)
                ? 'management_report.share_' . $channel
                : 'management_report.view';
        }
        $user = auth()->user();

        if ($permission && $user && TenantConnection::schema()->hasTable('permissions')) {
            $known = TenantConnection::db()->table('permissions')->where('name', $permission)->exists();
            $isSuperadmin = method_exists($user, 'can') && $user->can('superadmin');
            if ($known && !$isSuperadmin && method_exists($user, 'can') && !$user->can($permission)) {
                abort(403, 'You are not authorised for this Management Report action.');
            }
        }

        return $next($request);
    }
}
