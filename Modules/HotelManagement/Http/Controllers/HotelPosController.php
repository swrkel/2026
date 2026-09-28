<?php

namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class HotelPosController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::pos.index', [
            'charges' => $this->safeRows('hm_room_charges', 50),
            'orders' => $this->safeRows('hm_pos_orders', 50),
            'menuItems' => $this->safeRows('hm_pos_menu_items', 200),
            'categories' => $this->safeRows('hm_pos_categories', 100),
            'folios' => $this->activeOptions('hm_folios', 'folio_no'),
            'rooms' => $this->activeOptions('hm_rooms', 'room_no'),
        ]);
    }

    public function store(Request $request)
    {
        if ($request->input('form_type') === 'menu_item') {
            return $this->storeMenuItem($request);
        }

        if ($request->input('form_type') === 'pos_order') {
            return $this->storePosOrder($request);
        }

        return $this->storeCharge($request);
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'folio_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'charge_ref' => 'nullable|string|max:50',
            'charge_type' => 'required|string|max:50',
            'description' => 'required|string|max:191',
            'amount' => 'required|numeric',
            'charge_date' => 'required|date',
            'status' => 'nullable|string|max:30',
        ]);

        $data['source_module'] = 'hotel';
        $data['status'] = $data['status'] ?: 'posted';

        $this->updateScopedRow('hm_room_charges', (int) $id, $data);
        $this->syncFolioTotals((int) ($data['folio_id'] ?? 0));
        $this->audit('updated', 'hm_room_charges', (int) $id, $data);

        return back()->with('status', 'Room charge updated successfully.');
    }

    public function destroy($id)
    {
        $charge = $this->scopedQuery('hm_room_charges')->where('id', (int) $id)->first();
        $this->deleteScopedRow('hm_room_charges', (int) $id);
        if ($charge && !empty($charge->folio_id)) {
            $this->syncFolioTotals((int) $charge->folio_id);
        }
        $this->audit('deleted', 'hm_room_charges', (int) $id);

        return back()->with('status', 'Room charge deleted successfully.');
    }

    protected function storeCharge(Request $request)
    {
        $data = $request->validate([
            'folio_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'charge_ref' => 'nullable|string|max:50',
            'charge_type' => 'required|string|max:50',
            'description' => 'required|string|max:191',
            'amount' => 'required|numeric',
            'charge_date' => 'required|date',
            'status' => 'nullable|string|max:30',
        ]);

        $data = $this->withScope($data);
        $data['source_module'] = 'hotel';
        $data['status'] = $data['status'] ?: 'posted';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('hm_room_charges')->insertGetId($data);
        $this->syncFolioTotals((int) ($data['folio_id'] ?? 0));
        $this->audit('created', 'hm_room_charges', $id, $data);

        return back()->with('status', 'Room charge saved successfully.');
    }

    protected function storeMenuItem(Request $request)
    {
        $data = $request->validate([
            'category_name' => 'nullable|string|max:191',
            'category_id' => 'nullable|integer',
            'item_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:191',
            'price' => 'required|numeric|min:0',
            'unit' => 'nullable|string|max:30',
            'status' => 'nullable|string|max:30',
        ]);

        if (empty($data['category_id']) && !empty($data['category_name'])) {
            $category = $this->withScope([
                'name' => $data['category_name'],
                'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['category_name']), 0, 12)),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $data['category_id'] = DB::table('hm_pos_categories')->insertGetId($category);
        }

        unset($data['category_name']);
        $data = $this->withScope($data);
        $data['unit'] = $data['unit'] ?: 'unit';
        $data['status'] = $data['status'] ?: 'active';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('hm_pos_menu_items')->insertGetId($data);
        $this->audit('created', 'hm_pos_menu_items', $id, $data);

        return back()->with('status', 'POS menu item saved successfully.');
    }

    protected function storePosOrder(Request $request)
    {
        $data = $request->validate([
            'folio_id' => 'nullable|integer',
            'room_id' => 'nullable|integer',
            'order_date' => 'required|date',
            'guest_name' => 'nullable|string|max:191',
            'menu_item_id' => 'required|integer',
            'quantity' => 'required|numeric|min:0.0001',
            'discount_amount' => 'nullable|numeric|min:0',
            'service_charge' => 'nullable|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'payment_mode' => 'required|string|max:40',
            'note' => 'nullable|string',
        ]);

        $menuItem = $this->scopedQuery('hm_pos_menu_items')->where('id', (int) $data['menu_item_id'])->first();
        if (!$menuItem) {
            return back()->withErrors(['menu_item_id' => 'Selected POS item was not found for this business/location.'])->withInput();
        }

        $quantity = (float) $data['quantity'];
        $unitPrice = (float) $menuItem->price;
        $subtotal = round($quantity * $unitPrice, 4);
        $discount = (float) ($data['discount_amount'] ?? 0);
        $service = (float) ($data['service_charge'] ?? 0);
        $tax = (float) ($data['tax_amount'] ?? 0);
        $grandTotal = max(0, round($subtotal - $discount + $service + $tax, 4));

        $order = $this->withScope([
            'folio_id' => $data['folio_id'] ?? null,
            'room_id' => $data['room_id'] ?? null,
            'order_no' => $this->nextCode('hm_pos_orders', 'order_no', 'HPO'),
            'order_date' => $data['order_date'],
            'guest_name' => $data['guest_name'] ?? null,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'service_charge' => $service,
            'tax_amount' => $tax,
            'grand_total' => $grandTotal,
            'payment_mode' => $data['payment_mode'],
            'status' => $data['payment_mode'] === 'room' ? 'charged_to_room' : 'paid',
            'note' => $data['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::beginTransaction();
        try {
            $orderId = DB::table('hm_pos_orders')->insertGetId($order);
            $line = $this->withScope([
                'pos_order_id' => $orderId,
                'menu_item_id' => $menuItem->id,
                'description' => $menuItem->name,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $subtotal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('hm_pos_order_lines')->insert($line);

            if ($data['payment_mode'] === 'room' && !empty($data['folio_id'])) {
                $charge = $this->withScope([
                    'folio_id' => $data['folio_id'],
                    'room_id' => $data['room_id'] ?? null,
                    'charge_ref' => $order['order_no'],
                    'charge_type' => 'POS',
                    'description' => 'POS Order '.$order['order_no'].' - '.$menuItem->name,
                    'amount' => $grandTotal,
                    'charge_date' => $data['order_date'],
                    'source_module' => 'hotel_pos',
                    'status' => 'posted',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('hm_room_charges')->insert($charge);
                $this->syncFolioTotals((int) $data['folio_id']);
            }

            DB::commit();
            $this->audit('created', 'hm_pos_orders', $orderId, $order);
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return back()->with('status', 'POS order posted successfully.');
    }

    protected function syncFolioTotals(int $folioId): void
    {
        if ($folioId <= 0 || !$this->tableExists('hm_folios')) {
            return;
        }

        $charges = (float) $this->scopedQuery('hm_room_charges')->where('folio_id', $folioId)->sum('amount');
        $payments = $this->tableExists('hm_guest_payments')
            ? (float) $this->scopedQuery('hm_guest_payments')->where('folio_id', $folioId)->sum('amount')
            : 0.0;

        DB::table('hm_folios')->where('id', $folioId)->update([
            'total_charges' => $charges,
            'total_payments' => $payments,
            'balance' => $charges - $payments,
            'updated_at' => now(),
        ]);
    }
}
