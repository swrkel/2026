<?php
namespace Modules\DistributionNew\Services\Audit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Modules\DistributionNew\Entities\DisnewAuditResult;

class DisnewProductionAuditService{
 public function summary(): array{return ['module'=>'Distribution New','prefix'=>'disnew_','status'=>'ready_for_testing','checks'=>['permissions','menu','routes','sql','ui','tenant','business']];}
 public function permissionChecks(): array{return $this->rows(['disnew.view','disnew.sales_order.create','disnew.sales_invoice.create','disnew.loading.view','disnew.vehicle.view','disnew.audit.view']);}
 public function menuChecks(): array{return $this->rows(['Dashboard','Sales Orders','Sales Invoices','Loading','Unloading','Vehicles','Reports','Production Audit']);}
 public function routeChecks(): array{return $this->rows(['/distribution-new','/distribution-new/sales-orders','/distribution-new/sales-invoices','/distribution-new/loading','/distribution-new/audit']);}
 public function sqlChecks(): array{return $this->rows(['disnew_sales_orders','disnew_sales_invoices','disnew_vehicles','disnew_vehicle_stock','disnew_audit_checks','disnew_audit_results']);}
 public function uiChecks(): array{return $this->rows(['POS dashboard cards','standard toolbar','white button text','responsive table','modal spacing']);}
 private function rows(array $items): array{return array_map(fn($x)=>['name'=>$x,'status'=>'pending_server_verification','note'=>'Verify after upload in tenant database'], $items);}
 public function runFullAudit($user=null): array{DisnewAuditResult::create(['business_id'=>session('business.id'),'user_id'=>optional($user)->id,'check_type'=>'full','status'=>'queued','payload'=>$this->summary()]);return ['message'=>'Distribution New production audit queued/saved.'];}
}
