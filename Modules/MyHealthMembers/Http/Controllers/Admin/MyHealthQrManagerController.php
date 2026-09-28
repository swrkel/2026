<?php

namespace Modules\MyHealthMembers\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\MyHealthMembers\Entities\MyHealthMember;

class MyHealthQrManagerController extends Controller
{
    public function index()
    {
        $members = MyHealthMember::query()->orderByDesc('id')->paginate(25);
        return view('myhealthmembers::admin.qr.index', compact('members'));
    }

    public function reissue(MyHealthMember $member)
    {
        $member->forceFill(['qr_token' => bin2hex(random_bytes(24))])->save();
        return back()->with('status', 'QR token reissued successfully.');
    }

    public function revoke(MyHealthMember $member)
    {
        $member->forceFill(['qr_token' => null])->save();
        return back()->with('status', 'QR token revoked successfully.');
    }
}
