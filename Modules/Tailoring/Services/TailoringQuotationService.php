<?php
namespace Modules\Tailoring\Services;
use Illuminate\Support\Arr;
use Modules\Tailoring\Entities\TailoringQuotation;
class TailoringQuotationService
{
    public function create(array $data): TailoringQuotation
    {
        $data['quotation_no'] = $data['quotation_no'] ?? $this->nextNumber();
        $data['status'] = $data['status'] ?? 'draft';
        $data['totals'] = $this->calculateTotals($data['items'] ?? []);
        return TailoringQuotation::create($data);
    }
    public function calculateTotals(array $items): array
    {
        $subtotal = collect($items)->sum(fn($item) => (float) Arr::get($item, 'qty', 1) * (float) Arr::get($item, 'price', 0));
        $discount = (float) collect($items)->sum('discount');
        $tax = (float) collect($items)->sum('tax');
        return ['subtotal'=>$subtotal,'discount'=>$discount,'tax'=>$tax,'total'=>$subtotal - $discount + $tax];
    }
    public function convertToOrder(TailoringQuotation $quotation): array
    {
        $quotation->update(['status' => 'converted']);
        return ['customer_id'=>$quotation->customer_id,'business_location_id'=>$quotation->business_location_id,'quotation_id'=>$quotation->id,'items'=>$quotation->items,'total_amount'=>data_get($quotation->totals, 'total', 0)];
    }
    protected function nextNumber(): string { return 'TQ-' . now()->format('YmdHis'); }
}
