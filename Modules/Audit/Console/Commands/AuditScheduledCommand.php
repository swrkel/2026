<?php

namespace Modules\Audit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Modules\Audit\Models\AuditSchedule;
use Modules\Audit\Services\AuditContext;
use Modules\Audit\Services\AuditEngine;
use Modules\Audit\Services\TenantContextManager;

class AuditScheduledCommand extends Command
{
    protected $signature = 'audit:scheduled
        {--tenant= : Run schedules for one tenant ID or domain}
        {--all-tenants : Run due schedules tenant-by-tenant}';

    protected $description = 'Run due Audit schedules inside explicit tenant contexts.';

    public function handle(AuditEngine $engine, TenantContextManager $tenants)
    {
        if ($this->option('all-tenants')) {
            $executed = 0;
            $tenantCount = 0;

            foreach ($tenants->allTenants() as $tenant) {
                $tenantCount++;
                try {
                    $tenants->initialize($tenant);
                    $executed += $this->runDueForCurrentTenant($engine, $tenants);
                } catch (\Throwable $e) {
                    $key = method_exists($tenant, 'getTenantKey') ? $tenant->getTenantKey() : ($tenant->id ?? '?');
                    $this->warn('Tenant '.$key.' schedules skipped safely: '.$e->getMessage());
                } finally {
                    $tenants->end();
                }
            }

            $this->info('Checked '.$tenantCount.' tenant(s); executed '.$executed.' audit schedule(s).');
            return 0;
        }

        $initializedHere = false;
        try {
            if (!$tenants->initialized()) {
                $identifier = trim((string) $this->option('tenant'));
                if ($identifier === '') {
                    $this->error('No tenant context is active. Use --tenant=<tenant-id-or-domain> or --all-tenants.');
                    return 2;
                }

                $tenant = $tenants->resolve($identifier);
                if (!$tenant) {
                    $this->error('Tenant could not be resolved from: '.$identifier);
                    return 2;
                }

                $tenants->initialize($tenant);
                $initializedHere = true;
            }

            $count = $this->runDueForCurrentTenant($engine, $tenants);
            $this->info('Executed '.$count.' audit schedule(s).');
            return 0;
        } finally {
            if ($initializedHere) {
                $tenants->end();
            }
        }
    }

    protected function runDueForCurrentTenant(AuditEngine $engine, TenantContextManager $tenants): int
    {
        if (!Schema::hasTable('audit_schedules')) {
            return 0;
        }

        $now = now();
        $count = 0;

        foreach (AuditSchedule::where('is_enabled', 1)->get() as $schedule) {
            if (!$this->due($schedule, $now)) {
                continue;
            }

            try {
                $engine->run(
                    AuditContext::fromRuntime($schedule->business_id, null, null),
                    $schedule->modules ?: []
                );
                $schedule->update(['last_run_at' => $now]);
                $count++;
            } catch (\Throwable $e) {
                $this->warn('Schedule #'.$schedule->id.' ('.$schedule->name.') skipped safely: '.$e->getMessage());
            }
        }

        return $count;
    }

    protected function due($schedule, $now): bool
    {
        $timeDue = !$schedule->run_time || $now->format('H:i') >= substr($schedule->run_time, 0, 5);
        if (!$schedule->last_run_at) {
            return $schedule->frequency === 'hourly' ? true : $timeDue;
        }

        $last = $schedule->last_run_at;
        if ($schedule->frequency === 'hourly') return $last->diffInHours($now) >= 1;
        if ($schedule->frequency === 'daily') return !$last->isSameDay($now) && $timeDue;
        if ($schedule->frequency === 'weekly') return $last->diffInDays($now) >= 7 && $timeDue;
        if ($schedule->frequency === 'monthly') return $last->format('Y-m') !== $now->format('Y-m') && $timeDue;
        return false;
    }
}
