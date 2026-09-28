<?php
namespace Modules\RiceMill\Services;

class SidebarRegistrationService
{
    public function register(): void
    {
        $menu = config('ricemill.sidebar');
        foreach (['sidebar.manager','menu.manager','navigation.manager'] as $binding) {
            if (!app()->bound($binding)) continue;
            $manager = app($binding);
            foreach (['registerModule','register','addModule'] as $method) {
                if (method_exists($manager, $method)) { $manager->{$method}($menu); return; }
            }
        }
        // Host systems with automatic module/sidebar discovery can read Config/sidebar.php directly.
    }
}
