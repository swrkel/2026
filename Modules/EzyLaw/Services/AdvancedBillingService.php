<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawMatter,LawTimeEntry,LawExpense,LawBillingRate,LawInvoice,LawInvoiceAdjustment};
use Modules\EzyLaw\Utilities\EzyLawTenantGuard;
class AdvancedBillingService {
    public function resolveHourlyRate(LawMatter $matter, int $userId, $date=null): float {
        $date=$date ?: now()->toDateString();
        $q=LawBillingRate::where('active',1)->where(function($q)use($date){$q->whereNull('effective_from')->orWhere('effective_from','<=',$date);})->where(function($q)use($date){$q->whereNull('effective_to')->orWhere('effective_to','>=',$date);});
        $candidates=[
            (clone $q)->where('matter_id',$matter->id)->where('user_id',$userId)->first(),
            (clone $q)->where('matter_id',$matter->id)->whereNull('user_id')->first(),
            (clone $q)->whereNull('matter_id')->where('practice_area_id',$matter->practice_area_id)->where('user_id',$userId)->first(),
            (clone $q)->whereNull('matter_id')->whereNull('practice_area_id')->where('user_id',$userId)->first(),
            (clone $q)->whereNull('matter_id')->where('practice_area_id',$matter->practice_area_id)->whereNull('user_id')->first(),
        ];
        foreach($candidates as $r){if($r && (float)$r->hourly_rate>0)return (float)$r->hourly_rate;}
        return (float)$matter->hourly_rate;
    }
    public function preview(LawMatter $matter): array {
        $time=LawTimeEntry::where('matter_id',$matter->id)->where('billable',1)->where('invoiced',0)->orderBy('work_date')->get();
        foreach($time as $t){if((float)$t->rate<=0){$t->rate=$this->resolveHourlyRate($matter,(int)$t->user_id,$t->work_date);$t->amount=round(($t->minutes/60)*$t->rate,4);}}
        $expenses=LawExpense::where('matter_id',$matter->id)->where('billable',1)->where('invoiced',0)->orderBy('expense_date')->get();
        return ['time'=>$time,'expenses'=>$expenses,'time_total'=>(float)$time->sum('amount'),'expense_total'=>(float)$expenses->sum('amount')];
    }
    public function createMatterInvoice(LawMatter $matter,array $data): LawInvoice {
        return DB::transaction(function()use($matter,$data){
            $p=$this->preview($matter); $lines=[];
            foreach($p['time'] as $t){$lines[]=['source_type'=>'time','source_id'=>$t->id,'description'=>$t->description,'qty'=>round($t->minutes/60,4),'unit_price'=>(float)$t->rate];}
            foreach($p['expenses'] as $e){$lines[]=['source_type'=>'expense','source_id'=>$e->id,'description'=>$e->category.' - '.$e->description,'qty'=>1,'unit_price'=>(float)$e->amount];}
            if(!$lines) throw new \RuntimeException('No unbilled billable time or expenses found for this matter.');
            $invoice=app(BillingService::class)->createInvoice([
                'client_id'=>$matter->client_id,'matter_id'=>$matter->id,'invoice_date'=>$data['invoice_date'],
                'due_date'=>$data['due_date']??null,'tax_amount'=>$data['tax_amount']??0,'discount_amount'=>$data['discount_amount']??0,
                'notes'=>$data['notes']??null
            ],$lines);
            $p['time']->each(function($r){$r->update(['invoiced'=>1]);}); $p['expenses']->each(function($r){$r->update(['invoiced'=>1]);});
            return $invoice;
        });
    }
    public function adjust(LawInvoice $invoice,string $type,float $amount,string $reason): LawInvoiceAdjustment {
        return DB::transaction(function()use($invoice,$type,$amount,$reason){
            $amount=abs($amount); $signed=$type==='credit' ? -$amount : $amount;
            $adj=LawInvoiceAdjustment::create(['business_id'=>EzyLawTenantGuard::businessId(),'invoice_id'=>$invoice->id,'adjustment_type'=>$type,'amount'=>$amount,'reason'=>$reason,'created_by'=>auth()->id()]);
            $newTotal=max(0,(float)$invoice->total+$signed); $paid=(float)$invoice->paid_amount; $balance=max(0,$newTotal-$paid);
            $invoice->update(['total'=>$newTotal,'balance'=>$balance,'status'=>$balance<=0.0001?'paid':($paid>0?'partially_paid':'unpaid')]);
            return $adj;
        });
    }
}
