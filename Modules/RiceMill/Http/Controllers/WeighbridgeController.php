<?php
namespace Modules\RiceMill\Http\Controllers;

use Illuminate\Http\Request;
use Modules\RiceMill\Models\WeighbridgeEntry;

class WeighbridgeController extends BaseController
{
    public function index(Request $request)
    {
        $b = $this->bid();
        $query = WeighbridgeEntry::forBusiness($b)
            ->leftJoin('rcm_paddy_receipts as pr', function ($join) use ($b) {
                $join->on('pr.id', '=', 'rcm_weighbridge_entries.receipt_id')
                    ->where('pr.business_id', '=', $b);
            })
            ->leftJoin('rcm_paddy_varieties as pv', function ($join) use ($b) {
                $join->on('pv.id', '=', 'pr.paddy_variety_id')
                    ->where('pv.business_id', '=', $b);
            })
            ->select([
                'rcm_weighbridge_entries.id','rcm_weighbridge_entries.business_id',
                'rcm_weighbridge_entries.entry_no','rcm_weighbridge_entries.vehicle_no',
                'rcm_weighbridge_entries.supplier_id','rcm_weighbridge_entries.gross_weight',
                'rcm_weighbridge_entries.tare_weight','rcm_weighbridge_entries.net_weight',
                'rcm_weighbridge_entries.weighed_at','rcm_weighbridge_entries.receipt_id',
                'pv.code as paddy_code','pv.name as paddy_name',
            ]);

        $this->applyListFilters($query, $request, [
            'rcm_weighbridge_entries.entry_no','pv.code','pv.name',
            'rcm_weighbridge_entries.vehicle_no','rcm_weighbridge_entries.supplier_id',
            'rcm_weighbridge_entries.gross_weight','rcm_weighbridge_entries.tare_weight',
            'rcm_weighbridge_entries.net_weight','rcm_weighbridge_entries.receipt_id',
        ], 'rcm_weighbridge_entries.weighed_at', true);

        $rows = $query->latest('rcm_weighbridge_entries.id')
            ->paginate($this->listPerPage($request,25))
            ->appends($request->query());

        return view('RiceMill::weighbridge.index', compact('rows'));
    }
}
