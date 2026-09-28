<?php

namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\DistributionNew\Utils\DisnewTenantUtil;

class SmsController extends Controller
{
    public function __construct(protected DisnewTenantUtil $tenant) {}

    public function templates()
    {
        $templates = DB::table('disnew_sms_templates')->where('business_id', $this->tenant->businessId())->orderBy('event')->get();
        return view('distributionnew::sms.templates', compact('templates'));
    }

    public function saveTemplate(Request $request)
    {
        $businessId = $this->tenant->businessId();
        DB::table('disnew_sms_templates')->updateOrInsert(
            ['business_id'=>$businessId, 'event'=>$request->event],
            ['title'=>$request->title, 'message_template'=>$request->message_template, 'send_to_customer'=>$request->boolean('send_to_customer'), 'send_to_officers'=>$request->boolean('send_to_officers'), 'is_active'=>$request->boolean('is_active', true), 'updated_by'=>auth()->id(), 'updated_at'=>now(), 'created_at'=>now()]
        );
        return back()->with('status', 'SMS template saved.');
    }

    public function logs()
    {
        $logs = DB::table('disnew_sms_logs')->where('business_id', $this->tenant->businessId())->latest('id')->paginate(50);
        return view('distributionnew::sms.logs', compact('logs'));
    }
}
