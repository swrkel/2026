<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingMicrofinance\Entities\Group;
use Modules\BankingMicrofinance\Entities\Member;
use Modules\BankingMicrofinance\Services\NumberGenerator;

class GroupController extends Controller
{
    public function index(){ $groups = Group::where('business_id',$this->businessId())->latest()->paginate(25); return view('bankingmicrofinance::groups.index', compact('groups')); }
    public function create(){ $group = new Group(['status'=>'active']); return view('bankingmicrofinance::groups.form', compact('group')); }
    public function store(Request $request, NumberGenerator $numbers){
        $data = $this->validated($request); $data['business_id']=$this->businessId(); $data['location_id']=$this->locationId(); $data['group_no']=$numbers->next('bkg_mfi_groups','group_no',config('bankingmicrofinance.number_prefixes.group','MFG')); $data['created_by']=auth()->id();
        Group::create($data); return redirect()->route('banking.microfinance.groups.index')->with('status','Microfinance group created.');
    }
    public function edit(Group $group){ return view('bankingmicrofinance::groups.form', compact('group')); }
    public function update(Request $request, Group $group){ $group->update($this->validated($request)); return redirect()->route('banking.microfinance.groups.index')->with('status','Microfinance group updated.'); }
    public function show(Group $group){ $members = Member::where('group_id',$group->id)->latest()->paginate(25); return view('bankingmicrofinance::groups.show', compact('group','members')); }
    private function validated(Request $request): array { return $request->validate(['name'=>'required|string|max:255','center_name'=>'nullable|string|max:255','meeting_day'=>'nullable|string|max:50','meeting_time'=>'nullable','village'=>'nullable|string|max:255','field_officer'=>'nullable|string|max:255','status'=>'required|in:active,inactive,closed']); }
}
