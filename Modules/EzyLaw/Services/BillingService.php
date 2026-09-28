<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawInvoice,LawInvoiceLine,LawPayment,LawTimeEntry,LawExpense,LawAdvanceAllocation};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard,EzyLawReferenceGuard};
use Modules\EzyLaw\Contracts\FinanceGateway;
class BillingService
{
    public function createInvoice(array $data,array $lines): LawInvoice{
        return DB::transaction(function() use($data,$lines){
            EzyLawReferenceGuard::client($data['client_id'] ?? null);
            if (!empty($data['matter_id'])) EzyLawReferenceGuard::matterForClient($data['matter_id'], $data['client_id']);
            $subtotal=0; foreach($lines as $line){$subtotal += (float)$line['qty']*(float)$line['unit_price'];}
            $tax=(float)($data['tax_amount']??0); $discount=(float)($data['discount_amount']??0); $total=$subtotal+$tax-$discount;
            $invoice=LawInvoice::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'invoice_no'=>$data['invoice_no']??app(SettingsService::class)->nextNumber('invoice'),'subtotal'=>$subtotal,'total'=>$total,'paid_amount'=>0,'balance'=>$total,'status'=>'unpaid','created_by'=>auth()->id()]);
            foreach($lines as $line){ $qty=(float)$line['qty']; $price=(float)$line['unit_price']; LawInvoiceLine::create(['business_id'=>EzyLawTenantGuard::businessId(),'invoice_id'=>$invoice->id,'source_type'=>$line['source_type']??'manual','source_id'=>$line['source_id']??null,'description'=>$line['description'],'qty'=>$qty,'unit_price'=>$price,'tax_rate'=>$line['tax_rate']??0,'line_total'=>$qty*$price]); }
            app(ActivityService::class)->log('create','invoice',$invoice->id,'Invoice created'); return $invoice;
        });
    }
    public function recordPayment(LawInvoice $invoice,array $data): LawPayment{
        return DB::transaction(function() use($invoice,$data){
            $payment=LawPayment::create($data+['business_id'=>EzyLawTenantGuard::businessId(),'invoice_id'=>$invoice->id,'client_id'=>$invoice->client_id,'matter_id'=>$invoice->matter_id,'created_by'=>auth()->id()]);
            $paid=(float)$invoice->payments()->sum('amount')+(float)LawAdvanceAllocation::where('invoice_id',$invoice->id)->sum('amount'); $balance=max(0,(float)$invoice->total-$paid); $status=$balance<=0.0001?'paid':($paid>0?'partially_paid':'unpaid');
            $invoice->update(['paid_amount'=>$paid,'balance'=>$balance,'status'=>$status]); app(ActivityService::class)->log('payment','invoice',$invoice->id,'Payment recorded',['payment_id'=>$payment->id]); return $payment;
        });
    }
}
