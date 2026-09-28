<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class HotelInventoryController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        $items = $this->safeRows('hm_store_items', 100);
        $movements = $this->tableExists('hm_store_movements')
            ? $this->scopedQuery('hm_store_movements as m')
                ->leftJoin('hm_store_items as i', 'i.id', '=', 'm.store_item_id')
                ->select('m.*', 'i.item_code', 'i.name as item_name', 'i.unit')
                ->orderByDesc('m.id')
                ->limit(100)
                ->get()
            : collect();

        return view('hotelmanagement::inventory.index', compact('items', 'movements'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'item_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:191',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:30',
            'reorder_level' => 'nullable|numeric',
            'purchase_price' => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
            'status' => 'nullable|string|max:30',
        ]);

        $data['item_code'] = $data['item_code'] ?: $this->nextCode('hm_store_items', 'item_code', 'HMI');
        $data['unit'] = $data['unit'] ?: 'unit';
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['purchase_price'] = $data['purchase_price'] ?? 0;
        $data['selling_price'] = $data['selling_price'] ?? 0;
        $data['current_stock'] = $data['current_stock'] ?? 0;
        $data['status'] = $data['status'] ?: 'active';
        $data = $this->withScope($data);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        $id = DB::table('hm_store_items')->insertGetId($data);
        $this->audit('created', 'hm_store_items', $id, $data);

        return back()->with('status', 'Store item saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'item_code' => 'nullable|string|max:50',
            'name' => 'required|string|max:191',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:30',
            'reorder_level' => 'nullable|numeric',
            'purchase_price' => 'nullable|numeric',
            'selling_price' => 'nullable|numeric',
            'status' => 'nullable|string|max:30',
        ]);

        $data['unit'] = $data['unit'] ?: 'unit';
        $data['reorder_level'] = $data['reorder_level'] ?? 0;
        $data['purchase_price'] = $data['purchase_price'] ?? 0;
        $data['selling_price'] = $data['selling_price'] ?? 0;
        $data['status'] = $data['status'] ?: 'active';

        $this->updateScopedRow('hm_store_items', (int) $id, $data);
        $this->audit('updated', 'hm_store_items', (int) $id, $data);

        return back()->with('status', 'Store item updated successfully.');
    }

    public function destroy($id)
    {
        $this->deleteScopedRow('hm_store_items', (int) $id);
        $this->audit('deleted', 'hm_store_items', (int) $id);
        return back()->with('status', 'Store item deleted successfully.');
    }

    public function movement(Request $request)
    {
        $data = $request->validate([
            'store_item_id' => 'required|integer',
            'movement_type' => 'required|string|in:purchase,issue,adjustment,return,damage',
            'movement_date' => 'nullable|date',
            'quantity' => 'required|numeric|min:0.0001',
            'unit_cost' => 'nullable|numeric|min:0',
            'reference_no' => 'nullable|string|max:100',
            'note' => 'nullable|string|max:1000',
        ]);

        $item = $this->scopedQuery('hm_store_items')->where('id', (int) $data['store_item_id'])->first();
        if (!$item) {
            return back()->withErrors(['store_item_id' => 'Selected hotel store item was not found for this business/location.']);
        }

        $direction = in_array($data['movement_type'], ['purchase', 'return', 'adjustment'], true) ? 1 : -1;
        if ($data['movement_type'] === 'adjustment' && $request->input('adjustment_direction') === 'decrease') {
            $direction = -1;
        }

        $quantity = (float) $data['quantity'];
        $unitCost = (float) ($data['unit_cost'] ?? 0);
        $stockBefore = (float) ($item->current_stock ?? 0);
        $stockAfter = $stockBefore + ($direction * $quantity);

        if ($stockAfter < 0) {
            return back()->withErrors(['quantity' => 'Stock cannot become negative. Please check the issue/damage quantity.']);
        }

        $movement = $this->withScope([
            'store_item_id' => (int) $data['store_item_id'],
            'movement_no' => $this->nextCode('hm_store_movements', 'movement_no', 'HMM'),
            'movement_type' => $data['movement_type'],
            'movement_date' => $data['movement_date'] ?? now()->toDateString(),
            'direction' => $direction,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'reference_no' => $data['reference_no'] ?? null,
            'note' => $data['note'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::transaction(function () use ($movement, $stockAfter, $item) {
            $id = DB::table('hm_store_movements')->insertGetId($movement);
            $update = ['current_stock' => $stockAfter, 'updated_at' => now()];
            if (Schema::hasColumn('hm_store_items', 'last_movement_at')) {
                $update['last_movement_at'] = now();
            }
            $this->scopedQuery('hm_store_items')->where('id', $item->id)->update($update);
            $this->audit('created', 'hm_store_movements', $id, $movement);
        });

        return back()->with('status', 'Inventory movement posted and stock updated successfully.');
    }
}
