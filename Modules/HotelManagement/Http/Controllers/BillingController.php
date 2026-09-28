<?php
namespace Modules\HotelManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\HotelManagement\Http\Controllers\Concerns\HotelTenantContext;

class BillingController extends Controller
{
    use HotelTenantContext;

    public function index()
    {
        return view('hotelmanagement::billing.index', [
            'folios' => $this->scopedQuery('hm_folios as f')
                ->leftJoin('hm_guests as g', 'g.id', '=', 'f.guest_id')
                ->leftJoin('hm_reservations as r', 'r.id', '=', 'f.reservation_id')
                ->select('f.*', 'g.guest_name', 'r.reservation_no')
                ->latest('f.id')->limit(100)->get(),
            'guests' => $this->activeOptions('hm_guests','guest_name'),
            'reservations' => $this->activeOptions('hm_reservations','reservation_no'),
            'rooms' => $this->activeOptions('hm_rooms','room_no'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'folio_no'=>'nullable|string|max:50',
            'guest_id'=>'nullable|integer',
            'reservation_id'=>'nullable|integer',
            'folio_type'=>'nullable|string|max:30',
            'folio_date'=>'required|date',
            'total_charges'=>'nullable|numeric',
            'total_payments'=>'nullable|numeric',
            'status'=>'nullable|string|max:30',
        ]);

        $data['folio_no'] = $data['folio_no'] ?: $this->nextCode('hm_folios', 'folio_no', 'FOL');
        $data['folio_type'] = $data['folio_type'] ?: 'guest';
        $data['status'] = $data['status'] ?: 'open';
        $data['total_charges'] = $data['total_charges'] ?? 0;
        $data['total_payments'] = $data['total_payments'] ?? 0;
        $data['balance'] = $data['total_charges'] - $data['total_payments'];
        $data = $this->withScope($data);
        $data['created_at'] = now();
        $data['updated_at'] = now();
        $id = DB::table('hm_folios')->insertGetId($data);
        $this->audit('created', 'hm_folios', $id, $data);
        return back()->with('status', 'Folio saved successfully.');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'folio_no'=>'required|string|max:50',
            'guest_id'=>'nullable|integer',
            'reservation_id'=>'nullable|integer',
            'folio_type'=>'nullable|string|max:30',
            'folio_date'=>'required|date',
            'total_charges'=>'nullable|numeric',
            'total_payments'=>'nullable|numeric',
            'status'=>'nullable|string|max:30',
        ]);

        $data['folio_type'] = $data['folio_type'] ?: 'guest';
        $data['status'] = $data['status'] ?: 'open';
        $data['total_charges'] = $data['total_charges'] ?? 0;
        $data['total_payments'] = $data['total_payments'] ?? 0;
        $data['balance'] = $data['total_charges'] - $data['total_payments'];
        $this->updateScopedRow('hm_folios', (int) $id, $data);
        $this->audit('updated', 'hm_folios', (int) $id, $data);
        return back()->with('status', 'Folio updated successfully.');
    }

    public function destroy($id)
    {
        $this->deleteScopedRow('hm_folios', (int) $id);
        $this->audit('deleted', 'hm_folios', (int) $id);
        return back()->with('status', 'Folio deleted successfully.');
    }

    public function postCharge(Request $request, $id)
    {
        $data = $request->validate([
            'room_id' => 'nullable|integer',
            'charge_type' => 'required|string|max:50',
            'description' => 'required|string|max:255',
            'quantity' => 'nullable|numeric',
            'unit_price' => 'nullable|numeric',
            'tax' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'charge_date' => 'required|date',
        ]);
        $data['quantity'] = $data['quantity'] ?? 1;
        $data['unit_price'] = $data['unit_price'] ?? 0;
        $data['tax'] = $data['tax'] ?? 0;
        $data['discount'] = $data['discount'] ?? 0;
        $data['amount'] = ($data['quantity'] * $data['unit_price']) + $data['tax'] - $data['discount'];
        $data['folio_id'] = (int) $id;
        $data['line_type'] = 'charge';
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            DB::table('hm_folio_lines')->insert($data);
            $this->recalculateFolio((int) $id);
            $this->audit('charge_posted', 'hm_folios', (int) $id, $data);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['charge' => 'Charge could not be posted: '.$e->getMessage()])->withInput();
        }
        return back()->with('status', 'Folio charge posted successfully.');
    }

    public function postPayment(Request $request, $id)
    {
        $data = $request->validate([
            'payment_ref' => 'nullable|string|max:100',
            'payment_method' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'note' => 'nullable|string',
        ]);
        $data = $this->withScope($data);
        $data['folio_id'] = (int) $id;
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            DB::table('hm_guest_payments')->insert($data);
            $this->recalculateFolio((int) $id);
            $this->audit('payment_posted', 'hm_folios', (int) $id, $data);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['payment' => 'Payment could not be posted: '.$e->getMessage()])->withInput();
        }
        return back()->with('status', 'Folio payment posted successfully.');
    }

    public function close($id)
    {
        $this->recalculateFolio((int) $id);
        $folio = $this->scopedQuery('hm_folios')->where('id', (int) $id)->first();
        if ($folio && abs((float) $folio->balance) > 0.0001) {
            return back()->withErrors(['folio' => 'Cannot close folio while balance is not zero.']);
        }
        $this->updateScopedRow('hm_folios', (int) $id, ['status' => 'closed']);
        $this->audit('closed', 'hm_folios', (int) $id);
        return back()->with('status', 'Folio closed successfully.');
    }

    private function recalculateFolio(int $folioId): void
    {
        $charges = (float) DB::table('hm_folio_lines')->where('folio_id', $folioId)->sum('amount');
        $payments = (float) DB::table('hm_guest_payments')->where('folio_id', $folioId)->sum('amount');
        $this->updateScopedRow('hm_folios', $folioId, [
            'total_charges' => $charges,
            'total_payments' => $payments,
            'balance' => $charges - $payments,
        ]);
    }
}
