<?php

namespace Modules\BankingTreasury\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BankingTreasury\Services\TreasuryRegistryService;

class TreasurySettingController extends Controller
{
    public function index(TreasuryRegistryService $service)
    {
        return view('bankingtreasury::settings.index', [
            'title' => 'Treasury Settings',
            'settings' => $service->settings(),
        ]);
    }
}
