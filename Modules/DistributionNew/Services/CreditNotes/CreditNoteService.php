<?php

namespace Modules\DistributionNew\Services\CreditNotes;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewCreditNote;
use Modules\DistributionNew\Models\DisnewCreditNoteLine;
use Modules\DistributionNew\Models\DisnewReturn;
use Modules\DistributionNew\Services\Sms\DistributionNewSmsEventService;

class CreditNoteService
{
    public function createFromReturn(DisnewReturn $return): DisnewCreditNote
    {
        return DB::transaction(function () use ($return) {
            $note = DisnewCreditNote::create([
                'business_id' => $return->business_id,
                'business_location_id' => $return->business_location_id,
                'customer_id' => $return->customer_id,
                'sales_invoice_id' => $return->sales_invoice_id,
                'return_id' => $return->id,
                'credit_note_no' => 'DCN-' . now()->format('YmdHis') . '-' . $return->id,
                'credit_note_date' => now()->toDateString(),
                'status' => 'draft',
                'reason' => $return->reason,
                'subtotal' => $return->subtotal,
                'tax_total' => $return->tax_total,
                'total_amount' => $return->total_amount,
                'balance_amount' => $return->total_amount,
                'created_by' => auth()->id(),
            ]);
            foreach ($return->lines ?? [] as $line) {
                DisnewCreditNoteLine::create([
                    'business_id' => $return->business_id,
                    'credit_note_id' => $note->id,
                    'product_id' => $line->product_id,
                    'variation_id' => $line->variation_id,
                    'description' => $line->note,
                    'qty' => $line->qty,
                    'unit_price' => $line->unit_price,
                    'tax_amount' => $line->tax_amount,
                    'line_total' => $line->line_total,
                ]);
            }
            app(DistributionNewSmsEventService::class)->queue('credit_note_created', $note);
            return $note;
        });
    }

    public function approve(DisnewCreditNote $note): DisnewCreditNote
    {
        $note->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);
        app(DistributionNewSmsEventService::class)->queue('credit_note_approved', $note);
        return $note->refresh();
    }
}
