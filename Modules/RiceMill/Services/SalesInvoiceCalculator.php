<?php

namespace Modules\RiceMill\Services;

/**
 * Authoritative pricing rules for Rice Mill Sales Invoices.
 *
 * Discounts may be applied in BOTH ways on the same invoice:
 *  1. Per-unit discount on each sales line (Fixed amount per unit or Percentage
 *     of Unit Price).
 *  2. Invoice-level discount (Fixed amount or Percentage) after all per-unit
 *     discounts have been deducted.
 *
 * Tax is ALWAYS percentage-based and is calculated only after BOTH discounts:
 *
 * Gross Subtotal
 * - Total Per-unit Discounts
 * = Subtotal After Unit Discount
 * - Invoice Discount
 * = Taxable Amount
 * + (Taxable Amount x Tax %)
 * = Net Total
 *
 * Persisted currency amounts are rounded to 4 decimals, while display precision
 * continues to follow Business Settings.
 */
class SalesInvoiceCalculator
{
    public function calculate(array $lines, string $discountType, float $discountValue, float $taxPercent): array
    {
        $discountType = $this->discountType($discountType, 'Invoice Discount Type');

        if ($discountValue < 0) {
            throw new \InvalidArgumentException('Invoice Discount Value cannot be negative.');
        }
        if ($discountType === 'percentage' && $discountValue > 100) {
            throw new \InvalidArgumentException('Invoice Percentage Discount cannot be more than 100%.');
        }
        if ($taxPercent < 0 || $taxPercent > 100) {
            throw new \InvalidArgumentException('Tax percentage must be between 0% and 100%.');
        }

        $grossSubtotal = 0.0;
        $unitDiscountTotal = 0.0;
        $subtotalAfterUnitDiscount = 0.0;
        $lineResults = [];

        foreach ($lines as $index => $line) {
            $qty = (float) ($line['quantity'] ?? 0);
            $unitPrice = (float) ($line['unit_price'] ?? 0);
            $unitDiscountType = $this->discountType(
                (string) ($line['unit_discount_type'] ?? 'fixed'),
                'Unit Discount Type'
            );
            $unitDiscountValue = (float) ($line['unit_discount_value'] ?? 0);

            if ($qty < 0) {
                throw new \InvalidArgumentException('Quantity cannot be negative.');
            }
            if ($unitPrice < 0) {
                throw new \InvalidArgumentException('Unit Price cannot be negative.');
            }
            if ($unitDiscountValue < 0) {
                throw new \InvalidArgumentException('Unit Discount Value cannot be negative.');
            }
            if ($unitDiscountType === 'percentage' && $unitDiscountValue > 100) {
                throw new \InvalidArgumentException('Unit Percentage Discount cannot be more than 100%.');
            }

            // A Fixed unit discount means a fixed currency amount PER UNIT.
            $unitDiscountPerUnit = $unitDiscountType === 'percentage'
                ? $this->money($unitPrice * $unitDiscountValue / 100)
                : $this->money($unitDiscountValue);

            if ($unitDiscountPerUnit > $unitPrice + 0.00005) {
                throw new \InvalidArgumentException('Fixed Unit Discount cannot be more than the Unit Price.');
            }

            $grossLineTotal = $this->money($qty * $unitPrice);
            $unitDiscountAmount = $this->money($qty * $unitDiscountPerUnit);
            if ($unitDiscountAmount > $grossLineTotal) {
                $unitDiscountAmount = $grossLineTotal;
            }

            $netUnitPrice = $this->money(max(0, $unitPrice - $unitDiscountPerUnit));
            $lineTotal = $this->money(max(0, $grossLineTotal - $unitDiscountAmount));

            $lineResults[$index] = [
                'gross_line_total' => $grossLineTotal,
                'unit_discount_type' => $unitDiscountType,
                'unit_discount_value' => $this->money($unitDiscountValue),
                'unit_discount_per_unit' => $unitDiscountPerUnit,
                'unit_discount_amount' => $unitDiscountAmount,
                'net_unit_price' => $netUnitPrice,
                'line_total' => $lineTotal,
            ];

            $grossSubtotal += $grossLineTotal;
            $unitDiscountTotal += $unitDiscountAmount;
            $subtotalAfterUnitDiscount += $lineTotal;
        }

        $grossSubtotal = $this->money($grossSubtotal);
        $unitDiscountTotal = $this->money($unitDiscountTotal);
        $subtotalAfterUnitDiscount = $this->money($subtotalAfterUnitDiscount);

        $invoiceDiscountAmount = $discountType === 'percentage'
            ? $this->money($subtotalAfterUnitDiscount * $discountValue / 100)
            : $this->money($discountValue);

        if ($invoiceDiscountAmount > $subtotalAfterUnitDiscount + 0.00005) {
            throw new \InvalidArgumentException('Fixed Invoice Discount cannot be more than the subtotal after Unit Discounts.');
        }

        $taxableAmount = $this->money(max(0, $subtotalAfterUnitDiscount - $invoiceDiscountAmount));

        // Tax is deliberately and exclusively percentage-based. This is the
        // authoritative value used for Preview, Draft save and Approval.
        $taxAmount = $this->money($taxableAmount * $taxPercent / 100);
        $netTotal = $this->money($taxableAmount + $taxAmount);

        return [
            'line_results' => $lineResults,
            // Backward-compatible key used by older callers.
            'line_totals' => array_map(static fn (array $row) => $row['line_total'], $lineResults),
            // Keep rcm_dispatches.subtotal as the original gross subtotal.
            'subtotal' => $grossSubtotal,
            'gross_subtotal' => $grossSubtotal,
            'unit_discount_amount' => $unitDiscountTotal,
            'subtotal_after_unit_discount' => $subtotalAfterUnitDiscount,
            'discount_type' => $discountType,
            'discount_value' => $this->money($discountValue),
            'discount_amount' => $invoiceDiscountAmount,
            'taxable_amount' => $taxableAmount,
            'tax_percent' => $this->money($taxPercent),
            'tax_amount' => $taxAmount,
            'net_total' => $netTotal,
        ];
    }

    private function discountType(string $type, string $label): string
    {
        $type = strtolower(trim($type));
        if (! in_array($type, ['fixed', 'percentage'], true)) {
            throw new \InvalidArgumentException($label.' must be Percentage or Fixed.');
        }
        return $type;
    }

    private function money(float $value): float
    {
        return round($value, 4);
    }
}
