<?php
namespace Modules\EzyLaw\Services;

use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawClientAdvance,LawAdvanceAllocation,LawInvoice};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard, EzyLawReferenceGuard};

class AdvanceService
{
    public function deposit(array $data): LawClientAdvance
    {
        $amount=round((float)$data['amount'],4); if($amount<=0) throw new \InvalidArgumentException('Advance amount must be greater than zero.');
        if (($data['advance_type'] ?? 'client') === 'client') {
            EzyLawReferenceGuard::client($data['client_id'] ?? null);
            if (!empty($data['matter_id'])) {
                EzyLawReferenceGuard::matterForClient($data['matter_id'], $data['client_id']);
            }
        } elseif (!empty($data['matter_id'])) {
            EzyLawReferenceGuard::matter($data['matter_id']);
        }
        return LawClientAdvance::create($data+[
            'business_id'=>EzyLawTenantGuard::businessId(),'advance_no'=>$data['advance_no']??app(SettingsService::class)->nextNumber('advance'),
            'amount'=>$amount,'allocated_amount'=>0,'balance'=>$amount,'status'=>'active','created_by'=>auth()->id(),
        ]);
    }

    public function allocate(LawClientAdvance $advance, LawInvoice $invoice, float $amount, array $data=[]): LawAdvanceAllocation
    {
        return DB::transaction(function() use($advance,$invoice,$amount,$data){
            $advance=LawClientAdvance::lockForUpdate()->findOrFail($advance->id); $invoice=LawInvoice::lockForUpdate()->findOrFail($invoice->id);
            if($advance->advance_type!=='client') throw new \RuntimeException('Only client advances can be allocated to invoices.');
            if((int)$advance->client_id!==(int)$invoice->client_id) throw new \RuntimeException('Advance and invoice must belong to the same client.');
            $amount=round(abs($amount),4); if($amount<=0) throw new \RuntimeException('Allocation amount must be greater than zero.');
            if($amount>(float)$advance->balance+0.0001) throw new \RuntimeException('Allocation exceeds the available client advance balance.');
            if($amount>(float)$invoice->balance+0.0001) throw new \RuntimeException('Allocation exceeds the invoice balance.');
            $allocation=LawAdvanceAllocation::create([
                'business_id'=>EzyLawTenantGuard::businessId(),'advance_id'=>$advance->id,'invoice_id'=>$invoice->id,
                'allocated_on'=>$data['allocated_on']??now()->toDateString(),'amount'=>$amount,'notes'=>$data['notes']??null,'created_by'=>auth()->id(),
            ]);
            $allocated=(float)$advance->allocations()->sum('amount');$balance=max(0,(float)$advance->amount-$allocated);
            $advance->update(['allocated_amount'=>$allocated,'balance'=>$balance,'status'=>$balance<=0.0001?'fully_allocated':'active']);
            $this->recalculateInvoice($invoice);
            app(ActivityService::class)->log('allocate','client_advance',$advance->id,'Client advance allocated',['invoice_id'=>$invoice->id,'allocation_id'=>$allocation->id]);
            return $allocation;
        });
    }

    public function recalculateInvoice(LawInvoice $invoice): void
    {
        $cash=(float)$invoice->payments()->sum('amount');
        $advance=(float)LawAdvanceAllocation::where('invoice_id',$invoice->id)->sum('amount');
        $paid=$cash+$advance; $balance=max(0,(float)$invoice->total-$paid);
        $invoice->update(['paid_amount'=>$paid,'balance'=>$balance,'status'=>$balance<=0.0001?'paid':($paid>0?'partially_paid':'unpaid')]);
    }
}
