<?php
namespace Modules\DealerManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use Modules\DealerManagement\Services\DistributionAvailabilityService;
use Modules\DealerManagement\Services\LoginCodeService;

class DealerController extends Controller
{
    public function index()
    {
        $businessId=(int)(session('business.id')??session('business_id')??session('user.business_id'));
        abort_unless(app(DistributionAvailabilityService::class)->enabledForBusiness($businessId),404);
        $dealers=DB::table('dlr_dealers')->where('business_id',$businessId)->orderBy('name')->simplePaginate(50);
        // Do not preload the full contacts table. Customer lookup is AJAX/type-ahead.
        $customers=collect();
        return view('dealermanagement::admin.dealers.index',compact('dealers','customers','businessId'));
    }


    public function customerSearch(Request $request)
    {
        $businessId=(int)(session('business.id')??session('business_id')??session('user.business_id'));
        abort_unless($businessId>0 && app(DistributionAvailabilityService::class)->enabledForBusiness($businessId),404);
        if (!Schema::hasTable('contacts')) return response()->json([]);
        $term=trim((string)$request->query('q',''));
        if (mb_strlen($term)<2) return response()->json([]);
        $rows=DB::table('contacts')->where('business_id',$businessId)->whereIn('type',['customer','both'])
            ->where(function($q) use($term){$q->where('name','like','%'.$term.'%')->orWhere('mobile','like','%'.$term.'%');})
            ->orderBy('name')->limit(20)->get(['id','name','mobile']);
        return response()->json($rows);
    }

    public function store(Request $request, LoginCodeService $codes)
    {
        $businessId=(int)(session('business.id')??session('business_id')??session('user.business_id'));
        abort_unless(app(DistributionAvailabilityService::class)->enabledForBusiness($businessId),404);
        $d=$request->validate(['customer_id'=>'nullable|integer','dealer_code'=>'required|string|max:30','name'=>'required|string|max:191','mobile'=>'nullable|string|max:50','email'=>'nullable|email','address'=>'nullable|string','admin_name'=>'required|string|max:191','admin_mobile'=>'nullable|string|max:50','admin_email'=>'nullable|email','notes'=>'nullable|string']);
        $temporary=Str::random(10);
        $loginCode=$codes->generate($businessId);
        DB::transaction(function() use($businessId,$d,$temporary,$loginCode){
            $dealerId=DB::table('dlr_dealers')->insertGetId(['business_id'=>$businessId,'customer_id'=>$d['customer_id']??null,'dealer_code'=>$d['dealer_code'],'name'=>$d['name'],'mobile'=>$d['mobile']??null,'email'=>$d['email']??null,'address'=>$d['address']??null,'status'=>'active','notes'=>$d['notes']??null,'created_by'=>auth()->id(),'created_at'=>now(),'updated_at'=>now()]);
            $outletId=DB::table('dlr_outlets')->insertGetId(['business_id'=>$businessId,'dealer_id'=>$dealerId,'outlet_code'=>'MAIN','name'=>'Main Outlet','address'=>$d['address']??null,'mobile'=>$d['mobile']??null,'is_default'=>1,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $roleId=DB::table('dlr_roles')->insertGetId(['business_id'=>$businessId,'dealer_id'=>$dealerId,'name'=>'Dealer Admin','is_system'=>1,'created_at'=>now(),'updated_at'=>now()]);
            $now=now(); $permissionRows=[]; foreach(array_keys(config('dealermanagement_permissions', require __DIR__.'/../../../Config/permissions.php')) as $permission){ $permissionRows[]=['role_id'=>$roleId,'permission_key'=>$permission,'created_at'=>$now,'updated_at'=>$now]; } if($permissionRows) DB::table('dlr_role_permissions')->insert($permissionRows);
            $userId=DB::table('dlr_users')->insertGetId(['business_id'=>$businessId,'dealer_id'=>$dealerId,'role_id'=>$roleId,'name'=>$d['admin_name'],'login_code'=>$loginCode,'email'=>$d['admin_email']??null,'mobile'=>$d['admin_mobile']??null,'password'=>Hash::make($temporary),'is_dealer_admin'=>1,'is_active'=>1,'must_change_password'=>1,'created_at'=>now(),'updated_at'=>now()]);
            DB::table('dlr_user_outlets')->insert(['dealer_user_id'=>$userId,'outlet_id'=>$outletId,'created_at'=>now()]);
        });
        return back()->with('status','Dealer created. Dealer Admin Login Code: '.$loginCode.' | Temporary Password: '.$temporary.' (copy this now).');
    }
}
