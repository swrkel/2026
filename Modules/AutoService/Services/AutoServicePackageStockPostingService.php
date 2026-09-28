<?php
namespace Modules\AutoService\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Modules\AutoService\Entities\AutoServiceInvoice;
use Modules\AutoService\Entities\AutoServicePackageStockMovement;

class AutoServicePackageStockPostingService
{
    public function post(AutoServiceInvoice $invoice): AutoServiceInvoice
    {
        return DB::transaction(function() use ($invoice) {
            $invoice = AutoServiceInvoice::with('lines')->lockForUpdate()->findOrFail($invoice->id);

            if ($invoice->stock_posted_at) return $invoice;

            foreach($invoice->lines as $line){
                if (!$this->isStockLine($line) || !$line->variation_id || (float)$line->quantity <= 0) continue;
                $this->deductVariation($invoice,$line);
            }

            $invoice->status = $invoice->status === 'draft' ? 'posted' : $invoice->status;
            $invoice->posted_at = $invoice->posted_at ?: now();
            $invoice->posted_by = $invoice->posted_by ?: auth()->id();
            $invoice->stock_posted_at = now();
            $invoice->save();

            return $invoice;
        });
    }

    private function isStockLine($line): bool
    {
        return (bool)($line->is_stock_item ?? false)
            || ($line->component_type ?? null) === 'stock_item'
            || ($line->line_type ?? null) === 'part';
    }

    private function deductVariation(AutoServiceInvoice $invoice, $line): void
    {
        if (!Schema::hasTable('variation_location_details')) {
            throw new RuntimeException('Inventory table variation_location_details is not available.');
        }

        $stock = DB::table('variation_location_details')
            ->where('variation_id',$line->variation_id)
            ->when($invoice->location_id, fn($q) => $q->where('location_id',$invoice->location_id))
            ->lockForUpdate()
            ->first();

        if (!$stock) {
            throw new RuntimeException('No location stock row found for '.$line->description.'.');
        }

        $available=(float)$stock->qty_available;
        $required=(float)$line->quantity;
        if ($available < $required) {
            throw new RuntimeException('Insufficient stock for '.$line->description.'. Available: '.$available.', required: '.$required.'.');
        }

        DB::table('variation_location_details')->where('id',$stock->id)->update([
            'qty_available'=>DB::raw('qty_available - '.sprintf('%.4F',$required))
        ]);

        AutoServicePackageStockMovement::create([
            'business_id'=>$invoice->business_id,
            'location_id'=>$invoice->location_id,
            'invoice_id'=>$invoice->id,
            'job_id'=>$invoice->job_id,
            'invoice_line_id'=>$line->id,
            'package_id'=>$line->package_id ?? null,
            'product_id'=>$line->product_id,
            'variation_id'=>$line->variation_id,
            'movement_type'=>'invoice_issue',
            'quantity'=>-$required,
            'unit_price'=>$line->unit_price,
            'reference_no'=>$invoice->invoice_no,
            'created_by'=>auth()->id(),
        ]);
    }
}
