<?php
namespace Modules\AirlineTicketingNew\Services\Readiness;
use Illuminate\Support\Facades\Schema;
class ProductionReadinessService {
 public function check(): array {$c=['app_key'=>!empty(config('app.key')),'queue_connection'=>!empty(config('queue.default')),'storage_writable'=>is_writable(storage_path()),'logs_writable'=>is_writable(storage_path('logs')),'settings_table'=>Schema::hasTable('atn_settings'),'tickets_table'=>Schema::hasTable('atn_tickets'),'permissions_table'=>Schema::hasTable('permissions')];return ['status'=>collect($c)->every(fn($v)=>$v)?'ready':'blocked','checks'=>$c,'checked_at'=>now()->toDateTimeString()];}
}
