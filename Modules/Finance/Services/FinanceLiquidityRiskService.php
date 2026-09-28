<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\FinanceCashFlowForecast;
use Modules\Finance\Entities\FinanceLiquidityRisk;
use Modules\Finance\Entities\FinanceTreasuryTransaction;

class FinanceLiquidityRiskService
{
    public static function monitor()
    {
        $business_id = session('business.id');

        $locations = \App\BusinessLocation::where(
            'business_id',
            $business_id
        )->get();

        foreach ($locations as $location) {

            $cash_in = FinanceTreasuryTransaction::where(
                'business_id',
                $business_id
            )
            ->where(
                'location_id',
                $location->id
            )
            ->whereIn(
                'treasury_type',
                ['cash_in', 'bank_deposit']
            )
            ->sum('amount');

            $cash_out = FinanceTreasuryTransaction::where(
                'business_id',
                $business_id
            )
            ->where(
                'location_id',
                $location->id
            )
            ->whereIn(
                'treasury_type',
                ['cash_out', 'bank_withdrawal']
            )
            ->sum('amount');

            $current_balance = $cash_in - $cash_out;

            $projected_inflows = FinanceCashFlowForecast::where(
                'business_id',
                $business_id
            )
            ->where(
                'location_id',
                $location->id
            )
            ->where(
                'forecast_type',
                'inflow'
            )
            ->sum('expected_amount');

            $projected_outflows = FinanceCashFlowForecast::where(
                'business_id',
                $business_id
            )
            ->where(
                'location_id',
                $location->id
            )
            ->where(
                'forecast_type',
                'outflow'
            )
            ->sum('expected_amount');

            $projected_balance =
                ($current_balance + $projected_inflows)
                - $projected_outflows;

            $threshold = 100000;

            if ($projected_balance >= $threshold) {
                continue;
            }

            $risk_level = 'medium';

            if ($projected_balance <= 0) {
                $risk_level = 'critical';
            } elseif ($projected_balance <= 50000) {
                $risk_level = 'high';
            }

            $existing = FinanceLiquidityRisk::where(
                'business_id',
                $business_id
            )
            ->where(
                'location_id',
                $location->id
            )
            ->where(
                'status',
                'open'
            )
            ->first();

            if (!empty($existing)) {
                continue;
            }

            $risk = FinanceLiquidityRisk::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $location->id,

                'risk_no' =>
                    'LQR-' . time(),

                'risk_type' =>
                    'Liquidity Risk',

                'risk_level' =>
                    $risk_level,

                'subject' =>
                    'Low Treasury Liquidity',

                'description' =>
                    'Projected liquidity below threshold.',

                'current_balance' =>
                    $current_balance,

                'projected_balance' =>
                    $projected_balance,

                'threshold_amount' =>
                    $threshold,

                'status' =>
                    'open',

                'created_by' =>
                    auth()->id(),
            ]);

            FinanceNotificationService::create(
                'Liquidity Risk',
                'Treasury Liquidity Alert',
                'Projected liquidity risk detected.',
                null,
                $risk_level,
                'finance_liquidity_risks',
                $risk->id,
                $location->id
            );

            FinanceAuditService::log(
                'Treasury Risk',
                'Liquidity Risk Detected',
                'Treasury liquidity risk detected.',
                'finance_liquidity_risks',
                $risk->id,
                null,
                $risk->toArray(),
                $location->id
            );
        }

        return true;
    }
}