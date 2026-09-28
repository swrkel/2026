<?php

namespace Modules\BankingMicrofinance\Http\Controllers;

use Illuminate\Http\Request;
use Modules\BankingMicrofinance\Entities\Group;
use Modules\BankingMicrofinance\Entities\Member;
use Modules\BankingMicrofinance\Services\NumberGenerator;

class MemberController extends Controller
{
    public function index(){ $members = Member::with('group')->where('business_id',$this->businessId())->latest()->paginate(25); return view('bankingmicrofinance::groups.members', compact('members')); }
    public function create(){ $member = new Member(['status'=>'active']); $groups = Group::where('business_id',$this->businessId())->orderBy('name')->get(); return view('bankingmicrofinance::groups.member_form', compact('member','groups')); }
    public function store(Request $request, NumberGenerator $numbers){ $data=$this->validated($request); $data['business_id']=$this->businessId(); $data['member_no']=$numbers->next('bkg_mfi_members','member_no',config('bankingmicrofinance.number_prefixes.member','MFM')); $data['created_by']=auth()->id(); Member::create($data); return redirect()->route('banking.microfinance.members.index')->with('status','Member created.'); }
    public function edit(Member $member){ $groups = Group::where('business_id',$this->businessId())->orderBy('name')->get(); return view('bankingmicrofinance::groups.member_form', compact('member','groups')); }
    public function update(Request $request, Member $member){ $member->update($this->validated($request)); return redirect()->route('banking.microfinance.members.index')->with('status','Member updated.'); }
    private function validated(Request $request): array { return $request->validate(['group_id'=>'required|exists:bkg_mfi_groups,id','customer_id'=>'nullable|integer','name'=>'required|string|max:255','nic_no'=>'nullable|string|max:50','mobile'=>'nullable|string|max:50','address'=>'nullable|string','joined_on'=>'nullable|date','compulsory_saving_balance'=>'nullable|numeric','status'=>'required|in:active,inactive,blacklisted,closed']); }
}
