<?php
namespace Modules\AutoService\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\AutoService\Entities\AutoServiceInspection;
use Modules\AutoService\Entities\AutoServiceInspectionItem;
use Modules\AutoService\Entities\AutoServiceVehicle;
use Modules\AutoService\Services\AutoServiceNumberService;
use Modules\AutoService\Services\SharedCustomerAdapter;

class InspectionController extends AutoServiceBaseController
{
    public function index()
    {
        $q = AutoServiceInspection::query();
        if ($this->businessId()) $q->where('business_id', $this->businessId());
        return view('autoservice::inspections.index', ['inspections' => $q->orderByDesc('id')->paginate(25)]);
    }

    public function create()
    {
        return view('autoservice::inspections.form', $this->formData(new AutoServiceInspection()));
    }

    public function store(Request $request)
    {
        $this->saveInspection($request);
        return redirect()->route('autoservice.inspections.index')->with('status','Inspection saved successfully.');
    }

    public function edit($id)
    {
        return view('autoservice::inspections.form', $this->formData(AutoServiceInspection::with('items')->findOrFail($id)));
    }

    public function update(Request $request, $id)
    {
        $this->saveInspection($request, $id);
        return redirect()->route('autoservice.inspections.index')->with('status','Inspection updated successfully.');
    }

    private function saveInspection(Request $request, $id = null)
    {
        return DB::transaction(function () use ($request, $id) {
            $inspection = $id ? AutoServiceInspection::findOrFail($id) : new AutoServiceInspection();
            $inspection->fill($request->only(['contact_id','vehicle_id','job_id','inspection_date','status','odometer','fuel_level','customer_remarks','advisor_remarks']));
            $inspection->business_id = $this->businessId();
            $inspection->location_id = $this->locationId();
            if (!$inspection->inspection_no) $inspection->inspection_no = app(AutoServiceNumberService::class)->nextInspectionNo($this->businessId());
            $inspection->save();
            AutoServiceInspectionItem::where('inspection_id', $inspection->id)->delete();
            foreach ($request->input('items', []) as $item) {
                if (empty($item['item_name'])) continue;
                AutoServiceInspectionItem::create([
                    'business_id' => $inspection->business_id,
                    'inspection_id' => $inspection->id,
                    'section' => $item['section'] ?? null,
                    'item_name' => $item['item_name'],
                    'condition' => $item['condition'] ?? null,
                    'note' => $item['note'] ?? null,
                ]);
            }
            return $inspection;
        });
    }

    private function formData($inspection)
    {
        return [
            'inspection' => $inspection,
            'vehicles' => AutoServiceVehicle::where('business_id',$this->businessId())->orderByDesc('id')->limit(100)->get(),
            'customers' => app(SharedCustomerAdapter::class)->list($this->businessId()),
        ];
    }
}
