<?php

namespace Modules\MyHealthMembers\Http\Controllers\DisasterRecovery;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\MyHealthMembers\Services\DisasterRecovery\MyHealthDisasterRecoveryService;

class MyHealthBackupController extends Controller
{
    public function index(Request $request, MyHealthDisasterRecoveryService $service)
    {
        $backups = $service->backupRecords($request->only(['status', 'backup_type']));
        return view('myhealthmembers::disaster_recovery.backups.index', compact('backups'));
    }

    public function store(Request $request, MyHealthDisasterRecoveryService $service)
    {
        $service->createBackup($request->all(), auth()->id());
        return redirect()->route('myhealth.disaster_recovery.backups.index')->with('status', 'My Health backup request queued successfully.');
    }
}
