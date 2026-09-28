<?php

namespace Modules\MyHealthMembers\Http\Controllers\DisasterRecovery;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Entities\MyHealthBackupRecord;
use Modules\MyHealthMembers\Services\DisasterRecovery\MyHealthDisasterRecoveryService;

class MyHealthRestoreController extends Controller
{
    public function index(Request $request, MyHealthDisasterRecoveryService $service)
    {
        $restores = $service->restoreRecords($request->only(['status']));
        $backups = MyHealthBackupRecord::where('status', 'completed')->orderByDesc('id')->limit(100)->get();
        return view('myhealthmembers::disaster_recovery.restores.index', compact('restores', 'backups'));
    }

    public function store(Request $request, MyHealthDisasterRecoveryService $service)
    {
        $service->createRestore($request->all(), auth()->id());
        return redirect()->route('myhealth.disaster_recovery.restores.index')->with('status', 'Restore request recorded for review.');
    }
}
