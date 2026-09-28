<?php

namespace Modules\DistributionNew\Services\Validation;

use Illuminate\Validation\ValidationException;

class DisnewDocumentValidationService
{
    public function validateBusinessLocation(array $payload): void
    {
        if (empty($payload['business_id'])) {
            throw ValidationException::withMessages(['business_id' => __('distributionnew::lang.business_required')]);
        }
        if (empty($payload['location_id'])) {
            throw ValidationException::withMessages(['location_id' => __('distributionnew::lang.location_required')]);
        }
    }

    public function validatePositiveLines(array $lines, string $qtyKey = 'quantity'): void
    {
        if (count($lines) < 1) {
            throw ValidationException::withMessages(['lines' => __('distributionnew::lang.at_least_one_line_required')]);
        }
        foreach ($lines as $index => $line) {
            if (!isset($line[$qtyKey]) || (float) $line[$qtyKey] <= 0) {
                throw ValidationException::withMessages(["lines.$index.$qtyKey" => __('distributionnew::lang.quantity_must_be_positive')]);
            }
        }
    }

    public function validateNoOverInvoice(float $orderedQty, float $alreadyInvoicedQty, float $newInvoiceQty): void
    {
        if (($alreadyInvoicedQty + $newInvoiceQty) > $orderedQty) {
            throw ValidationException::withMessages(['invoice_quantity' => __('distributionnew::lang.over_invoice_not_allowed')]);
        }
    }
}
