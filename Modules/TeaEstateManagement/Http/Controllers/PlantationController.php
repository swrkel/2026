<?php
namespace Modules\TeaEstateManagement\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
use Modules\TeaEstateManagement\Services\{AuditService,TeaFinanceBridgeService};

class PlantationController extends BaseTeaController
{
    public function index(TeaFinanceBridgeService $finance){ $d=$this->common(); $b=$this->businessId(); $d['estates']=$d['installed']?$this->scopeLocations(DB::table('tea_estates')->where('business_id',$b))->orderBy('name')->get():collect(); $d['fields']=$d['installed']?$this->scopeLocations(DB::table('tea_fields')->where('business_id',$b))->orderByDesc('id')->limit(200)->get():collect(); $d['varieties']=$d['installed']?DB::table('tea_varieties')->where('business_id',$b)->where('status','active')->pluck('name','id'):collect(); $d['activities']=$d['installed']?$this->scopeLocations(DB::table('tea_field_activities as a')->leftJoin('tea_fields as f','f.id','=','a.field_id')->where('a.business_id',$b),'a.location_id')->select('a.*','f.name as field_name')->orderByDesc('a.activity_date')->orderByDesc('a.id')->limit(200)->get():collect(); $d['financeAccounts']=$finance->accountOptions(); return view('teaestate::plantation.index',$d); }
    public function storeEstate(Request $r, AuditService $audit){ $r->validate(['name'=>'required|string|max:180','estate_code'=>'nullable|string|max:40','location_id'=>'nullable']); $loc=$this->locations->resolveRequired($r->location_id); $b=$this->businessId(); $code=$r->estate_code?:'EST-'.str_pad((string)(DB::table('tea_estates')->where('business_id',$b)->count()+1),4,'0',STR_PAD_LEFT); $id=DB::table('tea_estates')->insertGetId(['business_id'=>$b,'location_id'=>$loc,'estate_code'=>$code,'name'=>$r->name,'address'=>$r->address,'area_acres'=>(float)$r->area_acres,'status'=>'active','notes'=>$r->notes,'created_at'=>now(),'updated_at'=>now()]); $audit->log('create','estate',$id); return back()->with('tea_success','Estate created.'); }
    public function storeField(Request $r, AuditService $audit){ $r->validate(['estate_id'=>'required|integer','field_code'=>'required|string|max:40','name'=>'required|string|max:150']); $b=$this->businessId(); $estate=DB::table('tea_estates')->where('business_id',$b)->where('id',$r->estate_id)->first(); abort_if(!$estate,422,'Invalid estate.'); $id=DB::table('tea_fields')->insertGetId(['business_id'=>$b,'location_id'=>$estate->location_id,'estate_id'=>$estate->id,'division_id'=>$r->division_id,'field_code'=>$r->field_code,'name'=>$r->name,'variety_id'=>$r->variety_id,'planted_date'=>$r->planted_date,'area_acres'=>(float)$r->area_acres,'plant_count'=>(int)$r->plant_count,'elevation_meters'=>$r->elevation_meters,'status'=>'active','notes'=>$r->notes,'created_at'=>now(),'updated_at'=>now()]); $audit->log('create','field',$id); return back()->with('tea_success','Field created.'); }

    public function storeActivity(Request $r, TeaFinanceBridgeService $finance, AuditService $audit)
    {
        $r->validate(['location_id'=>'nullable','field_id'=>'required|integer','activity_date'=>'required|date','activity_type'=>'required|string|max:60','cost_amount'=>'nullable|numeric|min:0']);
        $loc=$this->locations->resolveRequired($r->location_id); $b=$this->businessId();
        $field=DB::table('tea_fields')->where('business_id',$b)->where('location_id',$loc)->where('id',$r->field_id)->first();
        abort_if(!$field,422,'Selected field does not belong to the Location.');
        $cost=(float)($r->cost_amount??0);
        if ($cost > 0 && $finance->available()) {
            abort_if(!$finance->isValidAccount((int) $r->finance_account_id), 422, 'Please select a valid Finance Cash/Bank Account for this field cost.');
        }
        DB::transaction(function () use ($r, $finance, $audit, $b, $loc, $field, $cost) {
            $id=DB::table('tea_field_activities')->insertGetId(['business_id'=>$b,'location_id'=>$loc,'field_id'=>$field->id,'activity_date'=>$r->activity_date,'activity_type'=>$r->activity_type,'quantity'=>$r->quantity,'unit'=>$r->unit,'cost_amount'=>$cost,'finance_account_id'=>$r->finance_account_id,'notes'=>$r->notes,'created_by'=>$this->context->userId(),'created_at'=>now(),'updated_at'=>now()]);
            if($cost>0) $finance->queue('field_activity_cost','field_activity',$id,$loc,'TEA-ACT-'.$id,$r->activity_date,$cost,['finance_account_id'=>(int)$r->finance_account_id,'field_id'=>(int)$field->id]);
            $audit->log('create','field_activity',$id);
        });
        return back()->with('tea_success','Field activity recorded.');
    }
}
