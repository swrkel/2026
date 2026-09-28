<?php

namespace Modules\RestaurantNew\Services;

use Illuminate\Support\Facades\DB;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenTicket;
use Modules\RestaurantNew\Entities\RestaurantNewKitchenTicketLine;
use Modules\RestaurantNew\Entities\RestaurantNewOrder;

class RestaurantKitchenService
{
    public function createTicketsForOrder(RestaurantNewOrder $order, int $userId): array
    {
        return DB::transaction(function () use ($order, $userId) {
            $order->load('lines');
            $grouped = [];

            foreach ($order->lines as $line) {
                $sectionId = $line->kitchen_section_id ?: 0;
                $grouped[$sectionId][] = $line;
            }

            $tickets = [];
            foreach ($grouped as $sectionId => $lines) {
                $ticket = RestaurantNewKitchenTicket::create([
                    'business_id' => $order->business_id,
                    'business_location_id' => $order->business_location_id,
                    'order_id' => $order->id,
                    'ticket_no' => $this->nextTicketNo($order->business_id, $order->business_location_id),
                    'kitchen_section_id' => $sectionId ?: null,
                    'ticket_type' => 'kot',
                    'status' => 'new',
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                foreach ($lines as $line) {
                    RestaurantNewKitchenTicketLine::create([
                        'business_id' => $order->business_id,
                        'business_location_id' => $order->business_location_id,
                        'kitchen_ticket_id' => $ticket->id,
                        'order_line_id' => $line->id,
                        'menu_item_id' => $line->menu_item_id,
                        'item_name' => $line->item_name,
                        'quantity' => $line->quantity,
                        'modifiers_text' => $line->modifiers_text ?? null,
                        'special_instruction' => $line->special_instruction ?? null,
                        'status' => 'new',
                        'created_by' => $userId,
                        'updated_by' => $userId,
                    ]);
                }

                $tickets[] = $ticket->fresh('lines');
            }

            return $tickets;
        });
    }

    public function queue(int $businessId, ?int $locationId = null, ?int $sectionId = null)
    {
        return RestaurantNewKitchenTicket::with('lines')
            ->where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('business_location_id', $locationId))
            ->when($sectionId, fn ($q) => $q->where('kitchen_section_id', $sectionId))
            ->whereIn('status', ['new', 'printed', 'preparing'])
            ->orderBy('created_at')
            ->get();
    }

    public function updateTicketStatus(RestaurantNewKitchenTicket $ticket, string $status, int $userId, ?string $reason = null): RestaurantNewKitchenTicket
    {
        $now = now();
        $data = ['status' => $status, 'updated_by' => $userId];

        if ($status === 'printed') $data['printed_at'] = $now;
        if ($status === 'preparing') $data['started_at'] = $now;
        if ($status === 'completed') $data['completed_at'] = $now;
        if ($status === 'cancelled') {
            $data['cancelled_at'] = $now;
            $data['cancel_reason'] = $reason;
        }

        $ticket->update($data);
        $ticket->lines()->update(['status' => $status, 'updated_by' => $userId]);

        return $ticket->fresh('lines');
    }

    protected function nextTicketNo(int $businessId, ?int $locationId): string
    {
        $prefix = 'KOT-' . now()->format('ymd') . '-';
        $count = RestaurantNewKitchenTicket::where('business_id', $businessId)
            ->when($locationId, fn ($q) => $q->where('business_location_id', $locationId))
            ->whereDate('created_at', today())
            ->count() + 1;

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
