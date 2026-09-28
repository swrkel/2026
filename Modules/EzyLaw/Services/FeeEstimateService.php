<?php
namespace Modules\EzyLaw\Services;

use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawFeeEstimate,LawFeeEstimateLine,LawInvoice};
use Modules\EzyLaw\Utilities\{EzyLawTenantGuard, EzyLawReferenceGuard};

class FeeEstimateService
{
    public function create(array $data, array $lines): LawFeeEstimate
    {
        return DB::transaction(function () use ($data, $lines) {
            if (!$lines) throw new \InvalidArgumentException('At least one estimate line is required.');
            EzyLawReferenceGuard::client($data['client_id'] ?? null);
            if (!empty($data['matter_id'])) {
                EzyLawReferenceGuard::matterForClient($data['matter_id'], $data['client_id']);
            }
            $subtotal=0.0;
            foreach($lines as $line){$subtotal += (float)$line['qty']*(float)$line['unit_price'];}
            $tax=(float)($data['tax_amount']??0); $discount=(float)($data['discount_amount']??0);
            $estimate=LawFeeEstimate::create($data+[
                'business_id'=>EzyLawTenantGuard::businessId(),
                'estimate_no'=>$data['estimate_no']??app(SettingsService::class)->nextNumber('estimate'),
                'subtotal'=>$subtotal,'total'=>max(0,$subtotal+$tax-$discount),'status'=>$data['status']??'draft',
                'created_by'=>auth()->id(),
            ]);
            foreach($lines as $i=>$line){
                $qty=(float)$line['qty'];$price=(float)$line['unit_price'];
                LawFeeEstimateLine::create([
                    'business_id'=>EzyLawTenantGuard::businessId(),'estimate_id'=>$estimate->id,'description'=>$line['description'],
                    'qty'=>$qty,'unit_price'=>$price,'tax_rate'=>(float)($line['tax_rate']??0),'line_total'=>$qty*$price,'sort_order'=>$i+1,
                ]);
            }
            app(ActivityService::class)->log('create','fee_estimate',$estimate->id,'Fee estimate created');
            return $estimate;
        });
    }

    public function setStatus(LawFeeEstimate $estimate, string $status): LawFeeEstimate
    {
        $allowed=['draft','sent','accepted','rejected'];
        if(!in_array($status,$allowed,true)) throw new \InvalidArgumentException('Invalid estimate status.');
        $estimate->update(['status'=>$status,'accepted_at'=>$status==='accepted'?now():$estimate->accepted_at]);
        return $estimate->fresh();
    }

    public function convertToInvoice(LawFeeEstimate $estimate, array $data=[]): LawInvoice
    {
        if($estimate->status==='converted' && $estimate->invoice_id) return LawInvoice::findOrFail($estimate->invoice_id);
        if($estimate->status!=='accepted') throw new \RuntimeException('Only an accepted fee estimate can be converted to an invoice.');
        return DB::transaction(function() use($estimate,$data){
            $estimate->load('lines');
            $lines=[];
            foreach($estimate->lines as $line){$lines[]=['source_type'=>'estimate','source_id'=>$line->id,'description'=>$line->description,'qty'=>$line->qty,'unit_price'=>$line->unit_price,'tax_rate'=>$line->tax_rate];}
            $invoice=app(BillingService::class)->createInvoice([
                'client_id'=>$estimate->client_id,'matter_id'=>$estimate->matter_id,'invoice_date'=>$data['invoice_date']??now()->toDateString(),
                'due_date'=>$data['due_date']??null,'tax_amount'=>$estimate->tax_amount,'discount_amount'=>$estimate->discount_amount,
                'notes'=>trim(($estimate->notes?:'').'\nConverted from estimate '.$estimate->estimate_no),
            ],$lines);
            $estimate->update(['status'=>'converted','invoice_id'=>$invoice->id]);
            return $invoice;
        });
    }
}
