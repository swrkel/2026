<?php
namespace Modules\DealerManagement\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Modules\DealerManagement\Services\DistributionAvailabilityService;
use Modules\DealerManagement\Services\Integration\DistributionNewAdapter;

class SyncController extends Controller
{
    public function index(){ $businessId=(int)(session('business.id')??session('business_id')??session('user.business_id')); abort_unless(app(DistributionAvailabilityService::class)->enabledForBusiness($businessId),404); return view('dealermanagement::admin.dealers.sync',compact('businessId')); }
    public function run(DistributionNewAdapter $adapter){ $businessId=(int)(session('business.id')??session('business_id')??session('user.business_id')); abort_unless(app(DistributionAvailabilityService::class)->enabledForBusiness($businessId),404); $result=$adapter->syncBusiness($businessId); return back()->with('status','Sync completed. Deliveries: '.$result['deliveries'].' | Returns: '.$result['returns'].' | Skipped: '.$result['skipped'].' | Errors: '.count($result['errors']))->with('sync_errors',$result['errors']); }
}
