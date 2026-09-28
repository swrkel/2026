<?php
namespace Modules\Ran\Integrations;
use App\ContactLedger;
use Illuminate\Support\Collection;
use Modules\Ran\Support\RanContext;
use Modules\Suppliers\Entities\Supplier;
class SupplierIntegrationService {
 public function dropdown(bool $prepend=true):Collection{$items=Supplier::query()->forCurrentBusiness()->supplierOnly()->where('active',1)->orderBy('name')->get(['id','name','mobile','email','supplier_business_name'])->mapWithKeys(fn($s)=>[$s->id=>trim(($s->supplier_business_name?:$s->name).($s->mobile?' - '.$s->mobile:''))]);return $prepend?$items->prepend('Please select',''):$items;}
 public function find(int $id):Supplier{return Supplier::query()->forCurrentBusiness()->supplierOnly()->findOrFail($id);}
 public function credit(int $contactId,float $amount,string $date,string $note):?ContactLedger{return $this->ledger($contactId,$amount,'credit',$date,$note);}
 public function debit(int $contactId,float $amount,string $date,string $note):?ContactLedger{return $this->ledger($contactId,$amount,'debit',$date,$note);}
 private function ledger(int $contactId,float $amount,string $type,string $date,string $note):?ContactLedger{if($amount<=0)return null;$this->find($contactId);return ContactLedger::createContactLedger(['business_id'=>RanContext::businessId(),'contact_id'=>$contactId,'amount'=>$amount,'type'=>$type,'sub_type'=>'ran','operation_date'=>$date,'created_by'=>RanContext::userId(),'note'=>$note],'ran');}
}
