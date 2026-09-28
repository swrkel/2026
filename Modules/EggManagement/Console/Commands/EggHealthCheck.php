<?php
namespace Modules\EggManagement\Console\Commands;
use Illuminate\Console\Command;use Illuminate\Support\Facades\Schema;
class EggHealthCheck extends Command
{
    protected $signature='egg:health';protected $description='Check Egg Management schema and optional common-module connections.';
    public function handle(){
        $schema=Schema::connection(config('egg.connection') ?: config('database.default'));
        $tables=['egg_sequences','egg_grades','egg_flocks','egg_collections','egg_collection_lines','egg_grading_runs','egg_grading_lines','egg_stock_lots','egg_stock_movements','egg_sales','egg_sale_lines','egg_purchases','egg_purchase_lines','egg_transfers','egg_transfer_lines','egg_adjustments','egg_adjustment_lines','egg_product_mappings','egg_integration_outbox','egg_share_links','egg_audit_logs','egg_access_grants'];
        $missing=[];foreach($tables as $t){if(!$schema->hasTable($t))$missing[]=$t;}
        if($missing){$this->error('Missing Egg tables: '.implode(', ',$missing));}else{$this->info('Egg schema: OK ('.count($tables).' tables).');}
        foreach(['Customers/Suppliers'=>config('egg.common.contacts_table','contacts'),'Products New'=>config('egg.common.products_table','products'),'Locations'=>config('egg.common.locations_table','business_locations'),'Stores'=>config('egg.common.stores_table','stores')] as $label=>$table){$this->line($label.': '.($schema->hasTable($table)?'available':'not found / optional'));}
        $this->line('SMS endpoint: '.(config('egg.sharing.sms_endpoint')?'configured':'not configured'));
        $this->line('Finance integration: '.config('egg.finance.mode','outbox').' (safe outbox table '.($schema->hasTable('egg_integration_outbox')?'available':'missing').')');
        return $missing?1:0;
    }
}
