<?php

namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class RoomServiceController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::room_service.index', [
            'orders' => $this->orders(),
            'menuItems' => $this->activeOptions('hm_pos_menu_items', 'name'),
            'folios' => $this->activeOptions('hm_folios', 'folio_no'),
            'rooms' => $this->activeOptions('hm_rooms', 'room_no'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_date' => 'required|date',
            'delivery_time' => 'nullable|date_format:H:i',
            'room_id' => 'required|integer',
            'folio_id' => 'nullable|integer',
            'guest_name' => 'nullable|string|max:191',
            'menu_item_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.0001',
            'service_charge' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'payment_mode' => 'required|string|max:40',
            'priority' => 'nullable|string|max:30',
            'note' => 'nullable|string',
        ]);

        $menuItem = $this->scopedQuery('hm_pos_menu_items')->where('id', (int) $data['menu_item_id'])->first();
        if (!$menuItem) {
            return back()->withErrors(['menu_item_id' => 'Selected room service item was not found for this business/location.'])->withInput();
        }

        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $menuItem->price;
        $subtotal = round($quantity * $unitPrice, 4);
        $service = (float) ($data['service_charge'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $grandTotal = round($subtotal + $service + $tax, 4);

        $order = $this->withScope([
            'room_service_no' => $this->nextCode('hm_room_service_orders', 'room_service_no', 'HRS'),
            'order_date' => $data['order_date'],
            'delivery_time' => $data['delivery_time'] ?? null,
            'room_id' => $data['room_id'],
            'folio_id' => $data['folio_id'] ?? null,
            'guest_name' => $data['guest_name'] ?? null,
            'menu_item_id' => $menuItem->id,
            'item_name' => $menuItem->name,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
            'service_charge' => $service,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'payment_mode' => $data['payment_mode'],
            'priority' => $data['priority'] ?? 'normal',
            'status' => 'ordered',
            'note' => $data['note'] ?? null,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::beginTransaction();
        try {
            $id = DB::table('hm_room_service_orders')->insertGetId($order);
            if ($data['payment_mode'] === 'room' && !empty($data['folio_id'])) {
                DB::table('hm_room_charges')->insert($this->withScope([
                    'folio_id' => $data['folio_id'],
                    'room_id' => $data['room_id'],
                    'charge_ref' => $order['room_service_no'],
                    'charge_type' => 'Room Service',
                    'description' => 'Room Service '.$order['room_service_no'].' - '.$menuItem->name,
                    'amount' => $grandTotal,
                    'charge_date' => $data['order_date'],
                    'source_module' => 'hotel_room_service',
                    'status' => 'posted',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $this->syncFolioTotals((int) $data['folio_id']);
            }
            DB::commit();
            $this->audit('created', 'hm_room_service_orders', $id, $order);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return back()->with('status', 'Room service order saved successfully.');
    }

    public function status(Request $request, $id)
    {
        $data = $request->validate(['status' => 'required|string|max:30']);
        $allowed = ['ordered','preparing','ready','delivered','cancelled'];
        if (!in_array($data['status'], $allowed, true)) {
            return back()->withErrors(['status' => 'Invalid room service status.']);
        }
        $payload = ['status' => $data['status']];
        if ($data['status'] === 'delivered') { $payload['delivered_at'] = now(); }
        $this->updateScopedRow('hm_room_service_orders', (int) $id, $payload);
        $this->audit('status_changed', 'hm_room_service_orders', (int) $id, $payload);
        return back()->with('status', 'Room service status updated successfully.');
    }

    protected function orders()
    {
        if (!$this->tableExists('hm_room_service_orders')) { return collect(); }
        return $this->scopedQuery('hm_room_service_orders as o')
            ->leftJoin('hm_rooms as r', 'r.id', '=', 'o.room_id')
            ->leftJoin('hm_folios as f', 'f.id', '=', 'o.folio_id')
            ->select('o.*', 'r.room_no', 'f.folio_no')
            ->orderBy('o.id', 'desc')
            ->limit(100)
            ->get();
    }

    protected function syncFolioTotals(int $folioId): void
    {
        if ($folioId <= 0 || !$this->tableExists('hm_folios') || !$this->tableExists('hm_room_charges')) { return; }
        $charges = (float) $this->scopedQuery('hm_room_charges')->where('folio_id', $folioId)->sum('amount');
        $payments = $this->tableExists('hm_guest_payments') ? (float) $this->scopedQuery('hm_guest_payments')->where('folio_id', $folioId)->sum('amount') : 0;
        $this->updateScopedRow('hm_folios', $folioId, [
            'total_charges' => $charges,
            'total_payments' => $payments,
            'balance' => round($charges - $payments, 4),
        ]);
    }
}
