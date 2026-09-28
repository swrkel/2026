<?php

namespace Modules\PetroGeneral\Services\PetroDashboard;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\PetroGeneral\Entities\FuelTank;

/**
 * Standalone data service for Petro General / Petro Dashboard.
 *
 * IMPORTANT:
 * The dashboard tank balance must reconcile with Petro General / Tank Management
 * and its Tank Transaction Details / Summary.  Therefore this service calculates
 * the current balance from the same live movement ledger sources instead of using
 * fuel_tanks.current_balance (which is only a stored/cache value and may be stale).
 *
 * Live balance = purchases + opening stock + dip-reset increases
 *              - sales - deleted purchases - dip-reset decreases
 *              + transfers in - transfers out
 *
 * The calculation is entirely inside PetroGeneral.  It does not call or depend on
 * the legacy Petro module, PetroPD, PetroDirect, PumperDashboard or another module.
 */
class PetroDashboardService
{
    public function build(int $businessId): array
    {
        $tanks = FuelTank::query()
            ->where('business_id', $businessId)
            ->orderBy('fuel_tank_number')
            ->get([
                'id',
                'fuel_tank_number',
                'fuel_type',
                'storage_volume',
                'current_balance',
            ]);

        // Build the current balance for every tank in one query set. This is the
        // same movement-ledger method used by PetroGeneral Fuel Tanks / Current Balance.
        $liveBalances = $this->getLiveTankBalances($businessId);

        $rows = $tanks->map(function ($tank) use ($liveBalances) {
            $tankId = (int) $tank->id;

            if (is_array($liveBalances)) {
                // Missing ledger row means the tank has no movements and therefore
                // its ledger balance is 0. Do NOT replace a legitimate zero with the
                // stored fuel_tanks.current_balance cache.
                $balance = array_key_exists($tankId, $liveBalances)
                    ? (float) $liveBalances[$tankId]
                    : 0.0;
            } else {
                // Legacy-schema safety only. Used only if the live ledger query itself
                // failed. This preserves page availability while the failure is logged.
                $balance = (float) ($tank->current_balance ?? 0);
            }

            $capacity = (float) ($tank->storage_volume ?? 0);
            $percentage = $capacity > 0 ? ($balance / $capacity) * 100 : 0;
            $percentage = max(0, min(100, $percentage));

            return (object) [
                'id' => $tankId,
                'fuel_tank_number' => (string) ($tank->fuel_tank_number ?? ''),
                'fuel_type' => (string) ($tank->fuel_type ?? ''),
                'current_balance' => $balance,
                'storage_volume' => $capacity,
                'fill_percentage' => round($percentage, 1),
            ];
        });

        return [
            'tanks' => $rows,
            'message' => $this->getGeneralMessage(),
            'as_of_date' => now()->toDateString(),
        ];
    }

    /**
     * Current per-tank quantity from the authoritative PetroGeneral movement ledger.
     *
     * This mirrors FuelTankController::getTankLedgerBalancesForFuelTankList() so the
     * Petro Dashboard and Tank Management cannot show two different balances for the
     * same tank.
     *
     * @return array<int,float>|null
     */
    private function getLiveTankBalances(int $businessId): ?array
    {
        try {
            if (
                ! Schema::hasTable('transactions') ||
                ! Schema::hasTable('tank_purchase_lines') ||
                ! Schema::hasTable('tank_sell_lines') ||
                ! Schema::hasTable('tank_transfers') ||
                ! Schema::hasTable('fuel_tanks')
            ) {
                return null;
            }

            $purchaseRows = DB::table('transactions as t')
                ->join('tank_purchase_lines as tpl', function ($join) {
                    $join->on('t.id', '=', 'tpl.transaction_id')
                        ->where('tpl.quantity', '!=', 0);
                })
                ->join('fuel_tanks as ft', 'tpl.tank_id', '=', 'ft.id')
                ->where('t.business_id', $businessId)
                ->where('ft.business_id', $businessId)
                ->whereNull('t.deleted_at')
                ->select([
                    'tpl.tank_id as fuel_tank_id',
                    't.id as source_id',
                    DB::raw("SUM(CASE
                        WHEN t.type IN ('purchase', 'opening_stock') THEN tpl.quantity
                        WHEN t.type = 'stock_adjustment'
                             AND COALESCE(t.sub_type, '') = 'dip_resetting'
                             AND COALESCE(t.stock_adjustment_type, '') = 'increase'
                            THEN tpl.quantity
                        ELSE 0
                    END) as purchase_qty"),
                    DB::raw("SUM(CASE
                        WHEN t.type = '_deleted_purchase' THEN tpl.quantity
                        ELSE 0
                    END) as sold_qty"),
                ])
                ->groupBy('tpl.tank_id', 't.id');

            $sellRows = DB::table('transactions as t')
                ->join('tank_sell_lines as tsl', 't.id', '=', 'tsl.transaction_id')
                ->join('fuel_tanks as ft', 'tsl.tank_id', '=', 'ft.id')
                ->where('t.business_id', $businessId)
                ->where('ft.business_id', $businessId)
                ->whereNull('t.deleted_at')
                ->whereNotIn('t.type', ['purchase', '_deleted_purchase'])
                ->select([
                    'tsl.tank_id as fuel_tank_id',
                    't.id as source_id',
                    DB::raw('0 as purchase_qty'),
                    DB::raw("SUM(CASE
                        WHEN t.type = 'stock_adjustment'
                             AND COALESCE(t.sub_type, '') = 'dip_resetting'
                             AND COALESCE(t.stock_adjustment_type, '') = 'decrease'
                            THEN tsl.quantity
                        WHEN t.type != 'stock_adjustment' THEN tsl.quantity
                        ELSE 0
                    END) as sold_qty"),
                ])
                ->groupBy('tsl.tank_id', 't.id');

            $transferInRows = DB::table('tank_transfers as tt')
                ->join('fuel_tanks as ft', 'tt.to_tank', '=', 'ft.id')
                ->where('tt.business_id', $businessId)
                ->where('ft.business_id', $businessId)
                ->select([
                    'tt.to_tank as fuel_tank_id',
                    DB::raw('tt.id as source_id'),
                    'tt.quantity as purchase_qty',
                    DB::raw('0 as sold_qty'),
                ]);

            $transferOutRows = DB::table('tank_transfers as tt')
                ->join('fuel_tanks as ft', 'tt.from_tank', '=', 'ft.id')
                ->where('tt.business_id', $businessId)
                ->where('ft.business_id', $businessId)
                ->select([
                    'tt.from_tank as fuel_tank_id',
                    DB::raw('tt.id as source_id'),
                    DB::raw('0 as purchase_qty'),
                    'tt.quantity as sold_qty',
                ]);

            $ledger = $purchaseRows
                ->unionAll($sellRows)
                ->unionAll($transferInRows)
                ->unionAll($transferOutRows);

            return DB::query()
                ->fromSub($ledger, 'tank_ledger')
                ->select('fuel_tank_id')
                ->selectRaw('SUM(COALESCE(purchase_qty, 0) - ABS(COALESCE(sold_qty, 0))) as balance_qty')
                ->groupBy('fuel_tank_id')
                ->pluck('balance_qty', 'fuel_tank_id')
                ->mapWithKeys(function ($balance, $tankId) {
                    return [(int) $tankId => (float) $balance];
                })
                ->all();
        } catch (\Throwable $e) {
            Log::error('PetroGeneral Petro Dashboard: unable to calculate live tank balances', [
                'business_id' => $businessId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Reproduces the optional general-message behaviour used by the legacy
     * Petro dashboard, but reads the settings table directly so this page does
     * not require App\System or App\Utils\Util/TransactionUtil.
     */
    private function getGeneralMessage(): ?array
    {
        if (! Schema::hasTable('system')) {
            return null;
        }

        $keys = [
            'general_message_petro_dashboard_checkbox',
            'customer_supplier_security_deposit_current_liability_message',
            'customer_supplier_security_deposit_current_liability_font_size',
            'customer_supplier_security_deposit_current_liability_color',
        ];

        $settings = DB::table('system')
            ->whereIn('key', $keys)
            ->pluck('value', 'key');

        $enabled = (int) ($settings->get('general_message_petro_dashboard_checkbox') ?? 0);
        $text = (string) ($settings->get('customer_supplier_security_deposit_current_liability_message') ?? '');

        if ($enabled !== 1 || trim($text) === '') {
            return null;
        }

        return [
            'text' => $text,
            'font_size' => max(10, (int) ($settings->get('customer_supplier_security_deposit_current_liability_font_size') ?? 14)),
            'color' => (string) ($settings->get('customer_supplier_security_deposit_current_liability_color') ?? '#333333'),
        ];
    }
}
