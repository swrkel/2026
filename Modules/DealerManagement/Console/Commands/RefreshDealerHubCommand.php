<?php
namespace Modules\DealerManagement\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\DealerManagement\Services\{HubDatabaseManager,HubFeedService};

class RefreshDealerHubCommand extends Command
{
    protected $signature = 'dealer-hub:refresh {hub_dealer_id? : Optional Dealer Hub ID}';
    protected $description = 'Refresh Dealer Hub product sources, deliveries, returns and re-order alerts.';

    public function handle(): int
    {
        $id=(int)($this->argument('hub_dealer_id')??0);
        $ids=app(HubDatabaseManager::class)->central(function() use($id){
            $q=DB::table('dlr_hub_dealers')->where('status','active');
            if($id>0)$q->where('id',$id);
            return $q->pluck('id')->map(fn($x)=>(int)$x)->all();
        });
        foreach($ids as $dealerId){
            $this->line("Refreshing Dealer Hub #{$dealerId}...");
            $r=app(HubFeedService::class)->refresh($dealerId);
            $this->info('Deliveries: '.$r['deliveries'].' | Returns: '.$r['returns'].' | Errors: '.count($r['errors']));
            foreach($r['errors'] as $e)$this->warn($e);
        }
        return self::SUCCESS;
    }
}
