<?php

namespace Modules\RiceMill\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InstallSidebarHook extends Command
{
    protected $signature = 'rcm:install-sidebar-hook {--remove : Remove the Rice Mill explicit sidebar hook}';

    protected $description = 'Safely install/remove the Rice Mill module-owned submenu hook in the host sidebar.';

    public function handle(): int
    {
        $targets = [
            resource_path('views/layouts/partials/sidebar.blade.php'),
            resource_path('views/layouts/sidebar.blade.php'),
            resource_path('views/sidebar.blade.php'),
        ];

        $existingTargets = array_values(array_filter($targets, static fn ($path) => File::exists($path)));

        if (empty($existingTargets)) {
            $this->error('No supported host sidebar file was found.');
            return 1;
        }

        $changed = 0;
        $foundHostMarker = 0;

        foreach ($existingTargets as $path) {
            $contents = File::get($path);

            if ($this->option('remove')) {
                $updated = preg_replace(
                    '/^[ \t]*@includeIf\([\'\"]ricemill::layouts_v2\.partials\.sidebar[\'\"]\)[ \t]*\{\{--[ \t]*RCM_EXPLICIT_SIDEBAR_HOOK[ \t]*--\}\}[ \t]*\R?/m',
                    '',
                    $contents
                );

                if ($updated !== $contents) {
                    $this->backup($path, $contents);
                    File::put($path, $updated);
                    $changed++;
                    $this->info('Removed Rice Mill sidebar hook from: '.$this->relative($path));
                }
                continue;
            }

            if (strpos($contents, 'RCM_EXPLICIT_SIDEBAR_HOOK') !== false) {
                $this->line('Rice Mill sidebar hook already installed in: '.$this->relative($path));
                continue;
            }

            $patterns = [
                "@includeIf('layouts.partials.automatic-module-sidebar')",
                '@includeIf("layouts.partials.automatic-module-sidebar")',
            ];

            $marker = null;
            foreach ($patterns as $candidate) {
                if (strpos($contents, $candidate) !== false) {
                    $marker = $candidate;
                    break;
                }
            }

            if ($marker === null) {
                continue;
            }

            $foundHostMarker++;
            $hook = "@includeIf('ricemill::layouts_v2.partials.sidebar') {{-- RCM_EXPLICIT_SIDEBAR_HOOK --}}\n";
            $updated = str_replace($marker, $hook.$marker, $contents);

            if ($updated !== $contents) {
                $this->backup($path, $contents);
                File::put($path, $updated);
                $changed++;
                $this->info('Installed Rice Mill submenu hook in: '.$this->relative($path));
            }
        }

        if ($this->option('remove')) {
            if ($changed === 0) {
                $this->line('No Rice Mill sidebar hook was installed. Nothing changed.');
            }
            return 0;
        }

        if ($changed === 0 && $foundHostMarker === 0) {
            $this->error('The automatic-module-sidebar include was not found in the supported sidebar files. No shared file was changed.');
            $this->line('Please send the current resources/views/layouts/partials/sidebar.blade.php if this server uses a different sidebar structure.');
            return 1;
        }

        $this->newLine();
        $this->info('Rice Mill sidebar integration is ready. Run: php artisan optimize:clear');
        return 0;
    }

    private function backup(string $path, string $contents): void
    {
        $dir = storage_path('app/ricemill-backups');
        File::ensureDirectoryExists($dir);

        $name = str_replace(['/', '\\'], '_', $this->relative($path));
        $backup = $dir.'/'.$name.'.'.date('Ymd_His').'.bak';
        File::put($backup, $contents);
    }

    private function relative(string $path): string
    {
        $base = rtrim(base_path(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        return strpos($path, $base) === 0 ? substr($path, strlen($base)) : $path;
    }
}
