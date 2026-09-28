<?php

namespace Modules\ExpensesNew\Services;

use Illuminate\Support\Facades\Route;
use Throwable;

class ModuleAvailabilityService
{
    /**
     * Resolve whether any candidate module is enabled without creating a hard
     * dependency on the host application's module manager implementation.
     */
    public function isEnabled(array $moduleNames, array $routeOrUriNeedles = []): bool
    {
        if ($this->enabledByModuleManager($moduleNames)) {
            return true;
        }

        if ($this->enabledByStatusesFile($moduleNames)) {
            return true;
        }

        return $this->enabledByRegisteredRoute($routeOrUriNeedles);
    }

    /**
     * Check the module manager/status activator only. A disabled module can
     * still have registered compatibility routes, so route presence must not
     * be treated as enabled when deciding whether to show fallback choices.
     */
    public function isExplicitlyEnabled(array $moduleNames): bool
    {
        return $this->enabledByModuleManager($moduleNames)
            || $this->enabledByStatusesFile($moduleNames);
    }

    protected function enabledByModuleManager(array $moduleNames): bool
    {
        try {
            if (! app()->bound('modules')) {
                return false;
            }

            $manager = app('modules');

            foreach ($moduleNames as $name) {
                /*
                 * IS2000: skip a module the manager does not know about.
                 *
                 * nwidart's isEnabled() resolves the module via findOrFail(), which
                 * throws ModuleNotFoundException when it is absent. The catch below
                 * handles that correctly, but report() then writes
                 *     Module [ChequeWriting] does not exist!
                 * to the tenant log at ERROR level every time an Expenses New form
                 * is opened. It is expected - these are OPTIONAL integrations and
                 * this method exists precisely to tolerate their absence - so it
                 * should not be logged as an error at all. Asking first keeps the
                 * log readable for faults that matter.
                 */
                if (method_exists($manager, 'has') && ! $manager->has($name)) {
                    continue;
                }

                if (method_exists($manager, 'isEnabled') && $manager->isEnabled($name)) {
                    return true;
                }

                if (! method_exists($manager, 'find')) {
                    continue;
                }

                $module = $manager->find($name);
                if (! $module) {
                    continue;
                }

                if (method_exists($module, 'isEnabled') && $module->isEnabled()) {
                    return true;
                }

                if (method_exists($module, 'isStatus') && $module->isStatus(true)) {
                    return true;
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return false;
    }

    protected function enabledByStatusesFile(array $moduleNames): bool
    {
        $configured = config('modules.activators.file.statuses-file');
        $paths = array_filter([
            is_string($configured) && $configured !== ''
                ? ($this->isAbsolutePath($configured) ? $configured : base_path($configured))
                : null,
            base_path('modules_statuses.json'),
        ]);

        foreach (array_unique($paths) as $path) {
            if (! is_file($path)) {
                continue;
            }

            $statuses = json_decode((string) file_get_contents($path), true);
            if (! is_array($statuses)) {
                continue;
            }

            foreach ($moduleNames as $name) {
                foreach ($statuses as $module => $enabled) {
                    if (strcasecmp((string) $module, (string) $name) === 0 && (bool) $enabled) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    protected function enabledByRegisteredRoute(array $needles): bool
    {
        if ($needles === []) {
            return false;
        }

        try {
            foreach (Route::getRoutes() as $route) {
                $haystack = strtolower(trim(($route->getName() ?? '') . ' ' . $route->uri()));

                foreach ($needles as $needle) {
                    if ($needle !== '' && str_contains($haystack, strtolower($needle))) {
                        return true;
                    }
                }
            }
        } catch (Throwable $exception) {
            report($exception);
        }

        return false;
    }

    protected function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || (bool) preg_match('/^[A-Za-z]:[\\\\\/]/', $path);
    }
}
