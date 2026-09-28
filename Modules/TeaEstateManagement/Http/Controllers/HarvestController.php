<?php

namespace Modules\TeaEstateManagement\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\TeaEstateManagement\Services\AuditService;
use Modules\TeaEstateManagement\Services\InventoryService;
use Modules\TeaEstateManagement\Services\NumberingService;
use Modules\TeaEstateManagement\Services\TeaFinanceBridgeService;

class HarvestController extends BaseTeaController
{
    public function index()
    {
        $d = $this->common();
        $b = $this->businessId();
        $d['harvests'] = $d['installed']
            ? $this->scopeLocations(DB::table('tea_harvests as h')
                ->leftJoin('tea_fields as f', 'f.id', '=', 'h.field_id')
                ->where('h.business_id', $b), 'h.location_id')
                ->select('h.*', 'f.name as field_name')
                ->orderByDesc('h.harvest_date')->orderByDesc('h.id')->limit(300)->get()
            : collect();
        $d['fields'] = $d['installed']
            ? $this->scopeLocations(DB::table('tea_fields')->where('business_id', $b))
                ->where('status', 'active')->orderBy('name')->get()
            : collect();

        return view('teaestate::harvests.index', $d);
    }

    public function store(
        Request $r,
        NumberingService $numbers,
        InventoryService $inventory,
        TeaFinanceBridgeService $finance,
        AuditService $audit
    ) {
        $r->validate([
            'location_id' => 'nullable',
            'field_id' => 'required|integer',
            'harvest_date' => 'required|date',
            'green_leaf_qty_kg' => 'required|numeric|min:0.001',
            'rejected_qty_kg' => 'nullable|numeric|min:0',
            'standard_cost_per_kg' => 'nullable|numeric|min:0',
        ]);

        $loc = $this->locations->resolveRequired($r->location_id);
        $b = $this->businessId();
        $field = DB::table('tea_fields')
            ->where('business_id', $b)->where('location_id', $loc)->where('id', $r->field_id)->first();
        abort_if(!$field, 422, 'Selected field does not belong to the Location.');

        $gross = (float) $r->green_leaf_qty_kg;
        $rejected = (float) ($r->rejected_qty_kg ?? 0);
        $accepted = max(0, $gross - $rejected);
        abort_if($accepted <= 0, 422, 'Accepted quantity must be greater than zero.');
        $standardCost = (float) ($r->standard_cost_per_kg ?? 0);

        [$id, $no] = DB::transaction(function () use ($r, $numbers, $inventory, $finance, $audit, $b, $loc, $field, $gross, $rejected, $accepted, $standardCost) {
            $no = $numbers->next('harvest', 'TEA-HRV');
            $id = DB::table('tea_harvests')->insertGetId([
                'business_id' => $b,
                'location_id' => $loc,
                'estate_id' => $field->estate_id,
                'division_id' => $field->division_id,
                'field_id' => $field->id,
                'harvest_no' => $no,
                'harvest_date' => $r->harvest_date,
                'plucker_reference' => $r->plucker_reference,
                'green_leaf_qty_kg' => $gross,
                'rejected_qty_kg' => $rejected,
                'accepted_qty_kg' => $accepted,
                'quality_grade' => $r->quality_grade,
                'moisture_percent' => $r->moisture_percent,
                'status' => 'received',
                'notes' => $r->notes,
                'created_by' => $this->context->userId(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $lot = $inventory->createLot($loc, 'green_leaf', $accepted, $standardCost, [
                'source_type' => 'harvest', 'source_id' => $id, 'notes' => $no,
            ]);
            DB::table('tea_harvests')->where('id', $id)->update(['inventory_lot_id' => $lot]);

            if ($standardCost > 0) {
                $finance->queue('harvest_inventory', 'harvest', $id, $loc, $no, $r->harvest_date, $accepted * $standardCost, [
                    'qty_kg' => $accepted, 'unit_cost' => $standardCost,
                ]);
            }
            $audit->log('create', 'harvest', $id);

            return [$id, $no];
        });

        return back()->with('tea_success', 'Harvest '.$no.' recorded and added to green-leaf inventory.');
    }
}
