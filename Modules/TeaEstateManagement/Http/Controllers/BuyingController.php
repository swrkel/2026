<?php

namespace Modules\TeaEstateManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TeaEstateManagement\Services\AuditService;
use Modules\TeaEstateManagement\Services\InventoryService;
use Modules\TeaEstateManagement\Services\NumberingService;
use Modules\TeaEstateManagement\Services\TeaFinanceBridgeService;

class BuyingController extends BaseTeaController
{
    public function index(TeaFinanceBridgeService $finance)
    {
        $d = $this->common();
        $b = $this->businessId();
        $d['purchases'] = $d['installed']
            ? $this->scopeLocations(DB::table('tea_leaf_purchases as p')
                ->leftJoin('tea_parties as s', 's.id', '=', 'p.supplier_id')
                ->where('p.business_id', $b), 'p.location_id')
                ->select('p.*', 's.name as supplier_name')
                ->orderByDesc('p.purchase_date')->orderByDesc('p.id')->limit(300)->get()
            : collect();
        $d['suppliers'] = $d['installed']
            ? DB::table('tea_parties')->where('business_id', $b)->whereIn('party_type', ['supplier', 'both'])
                ->where('status', 'active')->pluck('name', 'id')
            : collect();
        $d['financeAccounts'] = $finance->accountOptions();

        return view('teaestate::buying.index', $d);
    }

    public function store(Request $r, NumberingService $numbers, InventoryService $inventory, TeaFinanceBridgeService $finance, AuditService $audit)
    {
        $r->validate([
            'location_id' => 'nullable', 'purchase_date' => 'required|date', 'supplier_id' => 'required|integer',
            'gross_weight_kg' => 'required|numeric|min:0.001', 'tare_weight_kg' => 'nullable|numeric|min:0',
            'rejected_weight_kg' => 'nullable|numeric|min:0', 'rate_per_kg' => 'required|numeric|min:0',
        ]);
        $loc = $this->locations->resolveRequired($r->location_id);
        $b = $this->businessId();
        abort_if(!DB::table('tea_parties')->where('business_id', $b)->where('id', $r->supplier_id)
            ->whereIn('party_type', ['supplier', 'both'])->where('status', 'active')->exists(), 422, 'Invalid supplier.');

        $gross = (float) $r->gross_weight_kg;
        $tare = (float) ($r->tare_weight_kg ?? 0);
        abort_if($tare > $gross, 422, 'Tare weight cannot exceed gross weight.');
        $net = max(0, $gross - $tare);
        $rejected = (float) ($r->rejected_weight_kg ?? 0);
        abort_if($rejected > $net, 422, 'Rejected weight cannot exceed net weight.');
        $accepted = max(0, $net - $rejected);
        abort_if($accepted <= 0, 422, 'Accepted green leaf quantity must be greater than zero.');
        $rate = (float) $r->rate_per_kg;
        $grossAmount = $accepted * $rate;
        $qualityDeduction = (float) ($r->quality_deduction_amount ?? 0);
        $otherDeduction = (float) ($r->other_deduction_amount ?? 0);
        $deductions = $qualityDeduction + $otherDeduction;
        abort_if($deductions > $grossAmount + 0.005, 422, 'Deductions cannot exceed the gross purchase amount.');
        $amount = max(0, $grossAmount - $deductions);

        [, $no] = DB::transaction(function () use ($r, $numbers, $inventory, $finance, $audit, $b, $loc, $gross, $tare, $net, $rejected, $accepted, $rate, $grossAmount, $qualityDeduction, $otherDeduction, $amount) {
            $no = $numbers->next('leaf_purchase', 'TEA-BUY');
            $id = DB::table('tea_leaf_purchases')->insertGetId([
                'business_id' => $b, 'location_id' => $loc, 'purchase_no' => $no, 'purchase_date' => $r->purchase_date,
                'supplier_id' => $r->supplier_id, 'vehicle_no' => $r->vehicle_no, 'weighbridge_ref' => $r->weighbridge_ref,
                'source_estate_name' => $r->source_estate_name, 'gross_weight_kg' => $gross, 'tare_weight_kg' => $tare,
                'net_weight_kg' => $net, 'rejected_weight_kg' => $rejected, 'accepted_weight_kg' => $accepted,
                'quality_grade' => $r->quality_grade, 'rate_per_kg' => $rate, 'quality_deduction_amount' => $qualityDeduction,
                'other_deduction_amount' => $otherDeduction, 'gross_amount' => $grossAmount, 'net_amount' => $amount,
                'payment_status' => 'unpaid', 'status' => 'received', 'notes' => $r->notes,
                'created_by' => $this->context->userId(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $lot = $inventory->createLot($loc, 'green_leaf', $accepted, $accepted > 0 ? $amount / $accepted : 0, [
                'source_type' => 'leaf_purchase', 'source_id' => $id, 'notes' => $no,
            ]);
            DB::table('tea_leaf_purchases')->where('id', $id)->update(['inventory_lot_id' => $lot]);
            $finance->queue('leaf_purchase', 'leaf_purchase', $id, $loc, $no, $r->purchase_date, $amount, ['supplier_id' => (int) $r->supplier_id]);
            $audit->log('create', 'leaf_purchase', $id);
            return [$id, $no];
        });

        return back()->with('tea_success', 'Green leaf purchase '.$no.' recorded.');
    }

    public function pay(Request $r, TeaFinanceBridgeService $finance, AuditService $audit)
    {
        $r->validate(['purchase_id' => 'required|integer', 'amount' => 'required|numeric|min:0.01', 'paid_on' => 'required|date', 'method' => 'required|string|max:30']);
        $b = $this->businessId();
        $purchase = DB::table('tea_leaf_purchases')->where('business_id', $b)->where('id', $r->purchase_id)->first();
        abort_if(!$purchase, 404);
        $this->locations->resolveRequired($purchase->location_id);

        if ($finance->available()) {
            abort_if(!$finance->isValidAccount((int) $r->finance_account_id), 422, 'Please select a valid Finance Cash/Bank Account.');
        }

        DB::transaction(function () use ($r, $finance, $audit, $b, $purchase) {
            $locked = DB::table('tea_leaf_purchases')->where('business_id', $b)->where('id', $purchase->id)->lockForUpdate()->first();
            abort_if(!$locked, 404);
            $paid = (float) DB::table('tea_purchase_payments')->where('business_id', $b)->where('purchase_id', $locked->id)->sum('amount');
            $due = max(0, (float) $locked->net_amount - $paid);
            $amt = (float) $r->amount;
            abort_if($amt > $due + 0.005, 422, 'Payment exceeds amount due.');

            $id = DB::table('tea_purchase_payments')->insertGetId([
                'business_id' => $b, 'location_id' => $locked->location_id, 'purchase_id' => $locked->id,
                'paid_on' => $r->paid_on, 'method' => $r->method, 'reference_no' => $r->reference_no,
                'amount' => $amt, 'finance_account_id' => $r->finance_account_id, 'note' => $r->note,
                'created_by' => $this->context->userId(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            $newPaid = $paid + $amt;
            $status = $newPaid + 0.005 >= (float) $locked->net_amount ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');
            DB::table('tea_leaf_purchases')->where('id', $locked->id)->update(['payment_status' => $status, 'updated_at' => now()]);
            $finance->queue('purchase_payment', 'purchase_payment', $id, (int) $locked->location_id, $locked->purchase_no.'/PAY-'.$id, $r->paid_on, $amt, [
                'finance_account_id' => (int) $r->finance_account_id, 'purchase_id' => (int) $locked->id,
            ]);
            $audit->log('create', 'purchase_payment', $id);
        });

        return back()->with('tea_success', 'Supplier payment recorded.');
    }
}
