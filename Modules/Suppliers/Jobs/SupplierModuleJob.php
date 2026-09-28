<?php

namespace Modules\Suppliers\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SupplierModuleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $action;
    protected array $payload;

    public function __construct(string $action, array $payload = [])
    {
        $this->action = $action;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        // Intentionally empty base job for Suppliers module queue abstraction.
        // Specific supplier jobs should extend/replace this small module-local job.
    }
}
