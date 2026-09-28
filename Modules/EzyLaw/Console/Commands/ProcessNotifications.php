<?php
namespace Modules\EzyLaw\Console\Commands;
use Illuminate\Console\Command;
use Modules\EzyLaw\Services\{NotificationRuleService,NotificationService};
class ProcessNotifications extends Command
{
    protected $signature='ezylaw:notifications';
    protected $description='Generate EzyLaw rule-based reminders and dispatch due notification records for the active tenant database.';
    public function handle(NotificationRuleService $rules,NotificationService $notifications): int
    {
        $created=$rules->generate();$dispatched=$notifications->dispatchDue();
        $this->info("EzyLaw notifications: {$created} generated, {$dispatched} dispatched/queued.");
        return self::SUCCESS;
    }
}
