<?php

namespace Modules\PetroGeneral\Services\Dashboard;

use Illuminate\Support\Facades\DB;

class DashboardSummaryService
{

    /*
     * MA-002: only filter on deleted_at where the table actually has it.
     *
     * The dashboard died with
     *     Unknown column 'deleted_at' in 'WHERE'
     *
     * I wrote ->whereNull('deleted_at') on fuel_tanks, pumps and
     * pump_operators without checking. NONE of the three has that column -
     * fuel_tanks has 17, pumps 25, pump_operators 25, and no deleted_at
     * among them. So every query on this page failed, not just the first.
     *
     * This is the third time I have assumed a soft-delete column that was
     * not there, so it is now asked rather than assumed - once per table,
     * cached for the request.
     */
    private array $softDeleteCache = [];

    private function hasSoftDeletes(string $table): bool
    {
        if (! array_key_exists($table, $this->softDeleteCache)) {
            $this->softDeleteCache[$table] = \Illuminate\Support\Facades\Schema::hasColumn($table, 'deleted_at');
        }

        return $this->softDeleteCache[$table];
    }

    /**
     * Apply the not-deleted filter only when the table supports it.
     */
    private function excludeDeleted($query, string $table)
    {
        if ($this->hasSoftDeletes($table)) {
            $query->whereNull($table . '.deleted_at');
        }

        return $query;
    }

    public function getSummary(int $businessId): array
    {
        return [
            'active_tanks' => $this->excludeDeleted(DB::table('fuel_tanks')->where('business_id', $businessId), 'fuel_tanks')->count(),
            'active_pumps' => $this->excludeDeleted(DB::table('pumps')->where('business_id', $businessId), 'pumps')->count(),
            'pump_operators' => $this->excludeDeleted(DB::table('pump_operators')->where('business_id', $businessId), 'pump_operators')->count(),
        ];
    }

    public function getTankBalances(int $businessId)
    {
        return $this->excludeDeleted(
            DB::table('fuel_tanks')->where('fuel_tanks.business_id', $businessId),
            'fuel_tanks'
        )
            
            /*
             * MA-002: 'fuel_tank_name' does not exist on fuel_tanks.
             *
             * The table has 17 columns and that is not one of them:
             *   id, business_id, product_id, fuel_tank_number, fuel_type,
             *   location_id, storage_volume, current_balance, bulk_tank,
             *   tank_manufacturer, tank_manufacturer_phone, tank_capacity,
             *   unit_name, user_id, transaction_date, created_at, updated_at
             *
             * A tank is identified by its NUMBER, not a name. current_balance
             * is selected as well so the dashboard can show how full each tank
             * is rather than only its capacity.
             */
            /*
             * MA-002: product name joined here too, so the tank cards can say
             * which fuel each tank holds rather than repeating the tank number.
             */
            ->leftJoin('products', 'products.id', '=', 'fuel_tanks.product_id')
            ->select(
                'fuel_tanks.id',
                'fuel_tanks.fuel_tank_number',
                'fuel_tanks.fuel_type',
                'fuel_tanks.storage_volume',
                'fuel_tanks.current_balance',
                'products.name as product_name'
            )
            ->orderBy('fuel_tanks.fuel_tank_number')
            ->limit(24)
            ->get();
    }

    public function getPumpSummary(int $businessId)
    {
        return $this->excludeDeleted(
            // MA-002: qualified, because products also has a business_id and an
            // unqualified column would be ambiguous once the join below is added.
            DB::table('pumps')->where('pumps.business_id', $businessId),
            'pumps'
        )
            
            /*
             * MA-002: join products so the Pump Overview shows the product NAME.
             *
             * It was selecting product_id and the view printed it straight out,
             * so the column read "2", "11" - database ids, meaningless on a
             * dashboard. The id is kept for anything that needs it.
             *
             * leftJoin, not join: a pump with no product still appears, with a
             * blank name, rather than vanishing from the list.
             */
            ->leftJoin('products', 'products.id', '=', 'pumps.product_id')
            ->select(
                'pumps.id',
                'pumps.pump_no',
                'pumps.pump_name',
                'pumps.product_id',
                'products.name as product_name'
            )
            ->orderBy('pumps.pump_no')
            ->limit(24)
            ->get();
    }
}
