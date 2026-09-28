<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\EnterpriseSetting;
use Modules\AirlineTicketingNew\Services\Admin\EnterpriseSettingService;

class EnterpriseSettingController extends Controller
{
    public function index()
    {
        $records=EnterpriseSetting::query()
            ->where('business_id',(int)session('business.id'))
            ->orderBy('setting_group')
            ->orderBy('setting_key')
            ->paginate(50);

        return view('airlineticketingnew::admin.enterprise-settings.index',compact('records'));
    }

    public function store(Request $request,EnterpriseSettingService $service)
    {
        $data=$request->validate([
            'setting_key'=>['required','string','max:120'],
            'setting_group'=>['required','string','max:80'],
            'setting_value_json'=>['nullable'],
        ]);

        $value=is_string($data['setting_value_json']??null)
            ? json_decode($data['setting_value_json'],true)
            : ($data['setting_value_json']??null);

        $service->put((int)session('business.id'),$data['setting_key'],$value,$data['setting_group']);

        return back()->with('status',['success'=>1,'msg'=>'Enterprise setting saved successfully.']);
    }
}
