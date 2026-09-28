<?php
namespace Modules\AirlineTicketingNew\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use Modules\AirlineTicketingNew\Entities\BackupRecord;
use Modules\AirlineTicketingNew\Services\Backup\ModuleBackupService;

class BackupController extends Controller
{
    public function index()
    {
        $records = BackupRecord::query()
            ->where('business_id', (int) session('business.id'))
            ->latest('id')
            ->paginate(25);

        return view('airlineticketingnew::admin.backups.index', compact('records'));
    }

    public function store(ModuleBackupService $service)
    {
        $service->create((int) session('business.id'));

        return back()->with('status', [
            'success' => 1,
            'msg' => 'Airline Ticketing backup created successfully.',
        ]);
    }
}
