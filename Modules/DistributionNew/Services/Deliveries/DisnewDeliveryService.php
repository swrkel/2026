<?php

namespace Modules\DistributionNew\Services\Deliveries;

use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Models\DisnewDelivery;
use Modules\DistributionNew\Models\DisnewDeliveryLine;
use Modules\DistributionNew\Services\Sms\DisnewSmsEventService;

class DisnewDeliveryService
{
    public function createFromInvoice($invoice, array $lines, array $payload = []): DisnewDelivery
    {
        return DB::transaction(function () use ($invoice, $lines, $payload) {
            $delivery = DisnewDelivery::create(array_merge([
                'business_id' => $invoice->business_id,
                'business_location_id' => $invoice->business_location_id ?? null,
                'disnew_sales_order_id' => $invoice->disnew_sales_order_id ?? null,
                'disnew_sales_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'status' => 'pending',
                'delivery_date' => now()->toDateString(),
            ], $payload));

            foreach ($lines as $line) {
                DisnewDeliveryLine::create(array_merge($line, ['disnew_delivery_id' => $delivery->id]));
            }

            return $delivery;
        });
    }

    public function markDelivered(DisnewDelivery $delivery, array $payload = []): DisnewDelivery
    {
        $delivery->fill(array_merge($payload, [
            'status' => 'delivered',
            'delivered_at' => now(),
        ]))->save();

        app(DisnewSmsEventService::class)->queueEvent('delivery_completed', $delivery->business_id, $delivery->customer_id, [
            'delivery_no' => $delivery->delivery_no,
            'invoice_id' => $delivery->disnew_sales_invoice_id,
        ]);

        return $delivery;
    }
}
