<?php

namespace Modules\PetroDirectNew\Http\Controllers;

use Illuminate\Http\Request;
use Modules\PetroDirectNew\Entities\PdirectnewOperator;
use Modules\PetroDirectNew\Entities\PdirectnewPump;
use Modules\PetroDirectNew\Entities\PdirectnewSettlement;
use Modules\PetroDirectNew\Entities\PdirectnewShift;
use Modules\PetroDirectNew\Http\Requests\SettlementRequest;
use Modules\PetroDirectNew\Services\SettlementService;
use Modules\PetroDirectNew\Services\SharedMasterDataService;
use Modules\PetroDirectNew\Support\BusinessContext;
use Modules\PetroDirectNew\Support\PermissionGate;

class SettlementController extends Controller
{
    public function __construct(private SettlementService $service, private SharedMasterDataService $master, private BusinessContext $context) {}

    public function index(Request $request)
    {
        PermissionGate::authorize('petro_direct_new.settlements.view');
        $q=PdirectnewSettlement::query()->with('operator')->where('business_id',$this->context->requireBusiness());
        if($request->filled('location_id')) $q->where('location_id',$request->integer('location_id'));
        if($request->filled('status')) $q->where('status',$request->string('status'));
        if($request->filled('date_from')) $q->whereDate('transaction_date','>=',$request->input('date_from'));
        if($request->filled('date_to')) $q->whereDate('transaction_date','<=',$request->input('date_to'));
        if($request->filled('search')) $q->where(function($x)use($request){$s='%'.$request->string('search').'%';$x->where('settlement_no','like',$s)->orWhere('note','like',$s);});
        return view('petrodirectnew::settlements.index',['settlements'=>$q->latest('id')->paginate(25)->withQueryString(),'locations'=>$this->master->locations()]);
    }

    public function create()
    {
        PermissionGate::authorize('petro_direct_new.settlements.create');
        return view('petrodirectnew::settlements.form',$this->formData(new PdirectnewSettlement()));
    }

    public function store(SettlementRequest $request)
    {
        PermissionGate::authorize('petro_direct_new.settlements.create');
        $settlement=$this->service->create($request->validated());
        return redirect()->route('petro-direct-new.settlements.show',$settlement)->with('status','Direct settlement created successfully.');
    }

    public function show(int $id)
    {
        PermissionGate::authorize('petro_direct_new.settlements.view');
        $settlement=$this->find($id)->load(['meterSales','payments','otherSales','otherIncome','customerPayments','operator','shift']);
        return view('petrodirectnew::settlements.show',compact('settlement'));
    }

    public function edit(int $id)
    {
        PermissionGate::authorize('petro_direct_new.settlements.edit');
        $settlement=$this->find($id)->load(['meterSales','payments','otherSales','otherIncome','customerPayments']);
        abort_unless(in_array($settlement->status,['draft','reopened'],true),422);
        return view('petrodirectnew::settlements.form',$this->formData($settlement));
    }

    public function update(SettlementRequest $request,int $id)
    {
        PermissionGate::authorize('petro_direct_new.settlements.edit');
        $settlement=$this->service->update($this->find($id),$request->validated());
        return redirect()->route('petro-direct-new.settlements.show',$settlement)->with('status','Direct settlement updated successfully.');
    }

    public function finalize(int $id)
    {
        PermissionGate::authorize('petro_direct_new.settlements.finalize');
        $this->service->finalize($this->find($id));
        return back()->with('status','Direct settlement finalized.');
    }

    public function destroy(int $id)
    {
        PermissionGate::authorize('petro_direct_new.settlements.delete');
        $settlement=$this->find($id); abort_unless(in_array($settlement->status,['draft','reopened'],true),422); $settlement->delete();
        return redirect()->route('petro-direct-new.settlements.index')->with('status','Draft direct settlement deleted.');
    }

    public function print(int $id)
    {
        PermissionGate::authorize('petro_direct_new.settlements.print');
        $settlement=$this->find($id)->load(['meterSales','payments','otherSales','otherIncome','customerPayments','operator','shift']);
        return view('petrodirectnew::print.settlement',compact('settlement'));
    }

    private function find(int $id): PdirectnewSettlement
    {
        return PdirectnewSettlement::where('business_id',$this->context->requireBusiness())->findOrFail($id);
    }

    private function formData(PdirectnewSettlement $settlement): array
    {
        $businessId=$this->context->requireBusiness();
        return [
            'settlement'=>$settlement,'locations'=>$this->master->locations(),'products'=>$this->master->products(),
            'contacts'=>$this->master->contacts(),'accounts'=>$this->master->accounts(),
            'operators'=>PdirectnewOperator::where('business_id',$businessId)->where('is_active',1)->orderBy('name')->get(),
            'shifts'=>PdirectnewShift::where('business_id',$businessId)->whereIn('status',['open','closed'])->latest('id')->limit(200)->get(),
            'pumps'=>PdirectnewPump::where('business_id',$businessId)->where('status','active')->orderBy('pump_no')->get(),
        ];
    }
}
