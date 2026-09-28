<?php

namespace Modules\BankingMobileBanking\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingMobileBanking\Services\MobileBankingDashboardService;

class MobileBankingController extends Controller
{
    protected $dashboard;

    public function __construct(MobileBankingDashboardService $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    public function dashboard()
    {
        return view('bankingmobile::dashboard.index', ['summary' => $this->dashboard->summary()]);
    }

    public function registrations() { return view('bankingmobile::registration.index'); }
    public function devices() { return view('bankingmobile::devices.index'); }
    public function transfers() { return view('bankingmobile::transfers.index'); }
    public function beneficiaries() { return view('bankingmobile::beneficiaries.index'); }
    public function bills() { return view('bankingmobile::bills.index'); }
    public function qr() { return view('bankingmobile::qr.index'); }
    public function notifications() { return view('bankingmobile::notifications.index'); }
    public function settings() { return view('bankingmobile::settings.index'); }
}
