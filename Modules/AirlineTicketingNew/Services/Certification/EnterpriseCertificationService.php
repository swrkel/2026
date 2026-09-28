<?php
namespace Modules\AirlineTicketingNew\Services\Certification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class EnterpriseCertificationService {
 public function run(int $businessId): array {
  $checks=[
   'required_tables'=>$this->requiredTables(),
   'tenant_scope'=>$businessId>0,
   'duplicate_ticket_numbers'=>$this->duplicates($businessId),
   'unbalanced_journals'=>$this->unbalanced($businessId),
   'negative_invoice_due'=>$this->negativeDue($businessId),
   'permission_records'=>DB::table('permissions')->where('name','like','airline_ticketing_new.%')->exists(),
  ];
  $passed=collect($checks)->every(fn($v)=>is_bool($v)?$v:(is_numeric($v)?$v==0:!in_array(false,$v,true)));
  return ['status'=>$passed?'passed':'attention_required','business_id'=>$businessId,'checks'=>$checks,'checked_at'=>now()->toDateTimeString()];
 }
 private function requiredTables(): array {
  $tables=['atn_settings','atn_airlines','atn_airports','atn_passengers','atn_reservations','atn_tickets','atn_invoices','atn_payments','atn_refunds','atn_supplier_settlements','atn_operational_tasks','atn_journal_entries','atn_workflow_instances'];
  return collect($tables)->mapWithKeys(fn($t)=>[$t=>Schema::hasTable($t)])->all();
 }
 private function duplicates(int $b): int {return DB::table('atn_tickets')->where('business_id',$b)->select('ticket_no')->groupBy('ticket_no')->havingRaw('COUNT(*) > 1')->count();}
 private function unbalanced(int $b): int {return DB::table('atn_journal_entries')->where('business_id',$b)->whereRaw('ABS(total_debit-total_credit) > 0.0001')->count();}
 private function negativeDue(int $b): int {return DB::table('atn_invoices')->where('business_id',$b)->where('due_total','<',0)->count();}
}
