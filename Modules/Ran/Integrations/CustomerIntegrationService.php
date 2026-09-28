<?php
namespace Modules\Ran\Integrations;
use App\ContactLedger;
use Illuminate\Support\Collection;
use Modules\Customers\Entities\Customer;
use Modules\Ran\Support\RanContext;
class CustomerIntegrationService {
 public function dropdown(bool $prepend=true):Collection{$items=Customer::query()->forBusiness(RanContext::businessId())->customersOnly()->activeOnly()->orderBy('name')->get(['id','name','mobile','email'])->mapWithKeys(fn($c)=>[$c->id=>trim($c->name.($c->mobile?' - '.$c->mobile:''))]);return $prepend?$items->prepend('Walk-in customer',''):$items;}
 public function find(?int $id):?Customer{if(!$id)return null;return Customer::query()->forBusiness(RanContext::businessId())->customersOnly()->findOrFail($id);}
 public function contactDetails(?int $id):array{$c=$this->find($id);return $c?['name'=>$c->name,'mobile'=>$c->mobile,'email'=>$c->email]:[];}
 public function debit(int $contactId,float $amount,string $date,string $note):?ContactLedger{return $this->ledger($contactId,$amount,'debit',$date,$note);}
 public function credit(int $contactId,float $amount,string $date,string $note):?ContactLedger{return $this->ledger($contactId,$amount,'credit',$date,$note);}
 private function ledger(int $contactId,float $amount,string $type,string $date,string $note):?ContactLedger{if($amount<=0)return null;$this->find($contactId);return ContactLedger::createContactLedger(['business_id'=>RanContext::businessId(),'contact_id'=>$contactId,'amount'=>$amount,'type'=>$type,'sub_type'=>'ran','operation_date'=>$date,'created_by'=>RanContext::userId(),'note'=>$note],'ran');}
}
