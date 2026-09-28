<?php
namespace Modules\DealerManagement\Console\Commands;

use Illuminate\Console\Command;
use Modules\DealerManagement\Services\Integration\DistributionNewAdapter;

class SyncDistributionCommand extends Command
{
    protected $signature = 'dealer-management:sync-distribution {business_id}';
    protected $description = 'Synchronize completed Distribution New deliveries/returns into Dealer Management stock.';

    public function handle(DistributionNewAdapter $adapter): int
    {
        $result = $adapter->syncBusiness((int)$this->argument('business_id'));
        $this->info('Deliveries: '.$result['deliveries'].' | Returns: '.$result['returns'].' | Skipped: '.$result['skipped']);
        foreach($result['errors'] as $error) $this->error($error);
        return empty($result['errors']) ? self::SUCCESS : self::FAILURE;
    }
}
