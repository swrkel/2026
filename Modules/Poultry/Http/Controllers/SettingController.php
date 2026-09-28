<?php

namespace Modules\Poultry\Http\Controllers;

use Illuminate\Http\Request;
use Modules\Poultry\Entities\Setting;
use Modules\Poultry\Services\LedgerGateway;
use Modules\Poultry\Services\StockGateway;

/**
 * Module settings.
 *
 * The category id settings are what point the module at the tenant's existing
 * product catalogue - which categories hold feed, medication, vaccines, eggs
 * and live birds. Without them the item selects come back empty, so this is
 * the first screen to configure after install.
 */
class SettingController extends PoultryBaseController
{
    protected $stock;
    protected $ledger;

    public function __construct(StockGateway $stock, LedgerGateway $ledger)
    {
        $this->stock  = $stock;
        $this->ledger = $ledger;
    }

    public function index()
    {
        $this->authorizePermission('poultry.settings');

        $keys = [
            'point_of_lay_week', 'laying_cycle_weeks', 'egg_tray_size',
            'weight_sample_size', 'mortality_alert_pct',
            'feed_category_ids', 'medication_category_ids', 'vaccine_category_ids',
            'egg_category_ids', 'live_bird_category_ids', 'chick_category_ids',
        ];

        $settings = [];
        foreach ($keys as $key) {
            $settings[$key] = Setting::get($key);
        }

        return view('poultry::settings.index', [
            'settings'      => $settings,
            'stockEnabled'  => $this->stock->isAvailable(),
            'ledgerEnabled' => $this->ledger->isAvailable(),
        ]);
    }

    public function update(Request $request)
    {
        $this->authorizePermission('poultry.settings');

        $data = $request->validate([
            'point_of_lay_week'   => 'nullable|integer|min:1|max:40',
            'laying_cycle_weeks'  => 'nullable|integer|min:1|max:200',
            'egg_tray_size'       => 'nullable|integer|min:1|max:100',
            'weight_sample_size'  => 'nullable|integer|min:1|max:1000',
            'mortality_alert_pct' => 'nullable|numeric|min:0|max:100',
            'feed_category_ids'       => 'nullable|array',
            'medication_category_ids' => 'nullable|array',
            'vaccine_category_ids'    => 'nullable|array',
            'egg_category_ids'        => 'nullable|array',
            'live_bird_category_ids'  => 'nullable|array',
            'chick_category_ids'      => 'nullable|array',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                Setting::put($key, $value);
            }
        }

        return redirect()->route('poultry.settings')
            ->with('status', ['success' => 1, 'msg' => 'Settings saved.']);
    }
}
