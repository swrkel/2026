<?php
namespace Modules\EzyLaw\Http\Middleware;

use Closure;
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;

class EnsureEzyLawEnabled
{
    public function handle($request, Closure $next)
    {
        $businessId=EzyLawTenantGuard::businessId();
        $guard='App\\Utils\\SidebarPermissionUtil';
        if(class_exists($guard)){
            $enabled=false;
            foreach(['ezylaw','ezylaw_module','ezy_law','lawyer_management','lawyer_management_system'] as $key){
                try { if($guard::isEnabled($key,$businessId)){ $enabled=true; break; } } catch (\Throwable $e) {}
            }
            if(!$enabled) abort(403,'EzyLaw is not enabled for this business.');
        }
        return $next($request);
    }
}
