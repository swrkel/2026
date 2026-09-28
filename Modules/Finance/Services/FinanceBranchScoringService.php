<?php

namespace Modules\Finance\Services;

use Modules\Finance\Entities\BusinessLocation;
use Modules\Finance\Entities\FinanceBranchScore;
use Modules\Finance\Entities\FinanceKpiSnapshot;

class FinanceBranchScoringService
{
    public static function generate()
    {
        $business_id = session('business.id');

        /*
        |--------------------------------------------------------------------------
        | CLEAR OLD SCORES
        |--------------------------------------------------------------------------
        */

        FinanceBranchScore::where(
            'business_id',
            $business_id
        )->delete();

        /*
        |--------------------------------------------------------------------------
        | GET BUSINESS LOCATIONS
        |--------------------------------------------------------------------------
        */

        $locations = BusinessLocation::where(
            'business_id',
            $business_id
        )->get();

        /*
        |--------------------------------------------------------------------------
        | NO LOCATIONS SAFETY
        |--------------------------------------------------------------------------
        */

        if ($locations->count() == 0) {

            FinanceBranchScore::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    null,

                'score_date' =>
                    date('Y-m-d'),

                'profitability_score' =>
                    50,

                'liquidity_score' =>
                    50,

                'collection_score' =>
                    50,

                'treasury_score' =>
                    50,

                'efficiency_score' =>
                    50,

                'risk_score' =>
                    50,

                'overall_score' =>
                    50,

                'ranking_position' =>
                    1,

                'created_by' =>
                    auth()->id(),
            ]);

            return true;
        }

        foreach ($locations as $location) {

            $snapshot = FinanceKpiSnapshot::where(
                'business_id',
                $business_id
            )
            ->where(
                'location_id',
                $location->id
            )
            ->latest()
            ->first();

            /*
            |--------------------------------------------------------------------------
            | DEFAULT SCORES
            |--------------------------------------------------------------------------
            */

            $profitability_score = 60;
            $liquidity_score = 60;
            $collection_score = 60;
            $treasury_score = 60;
            $efficiency_score = 60;
            $risk_score = 60;

            /*
            |--------------------------------------------------------------------------
            | SNAPSHOT BASED SCORING
            |--------------------------------------------------------------------------
            */

            if (!empty($snapshot)) {

                if (
                    ($snapshot->gross_margin_percent ?? 0) >= 40
                ) {

                    $profitability_score = 95;

                } elseif (
                    ($snapshot->gross_margin_percent ?? 0) >= 30
                ) {

                    $profitability_score = 85;

                } elseif (
                    ($snapshot->gross_margin_percent ?? 0) >= 20
                ) {

                    $profitability_score = 70;
                }

                $liquidity_score = min(
                    100,
                    $snapshot->liquidity_score ?? 60
                );

                $collection_score = min(
                    100,
                    $snapshot->collection_efficiency_percent ?? 60
                );

                if (
                    ($snapshot->treasury_net_position ?? 0) > 0
                ) {

                    $treasury_score = 85;
                }

                if (
                    ($snapshot->expense_ratio_percent ?? 0) <= 40
                ) {

                    $efficiency_score = 90;

                } elseif (
                    ($snapshot->expense_ratio_percent ?? 0) <= 60
                ) {

                    $efficiency_score = 75;
                }

                if (
                    ($snapshot->liquidity_score ?? 60) < 50
                ) {

                    $risk_score = 40;
                }

            }

            /*
            |--------------------------------------------------------------------------
            | OVERALL SCORE
            |--------------------------------------------------------------------------
            */

            $overall_score =
                (
                    $profitability_score +
                    $liquidity_score +
                    $collection_score +
                    $treasury_score +
                    $efficiency_score +
                    $risk_score
                ) / 6;

            FinanceBranchScore::create([

                'business_id' =>
                    $business_id,

                'location_id' =>
                    $location->id,

                'score_date' =>
                    date('Y-m-d'),

                'profitability_score' =>
                    $profitability_score,

                'liquidity_score' =>
                    $liquidity_score,

                'collection_score' =>
                    $collection_score,

                'treasury_score' =>
                    $treasury_score,

                'efficiency_score' =>
                    $efficiency_score,

                'risk_score' =>
                    $risk_score,

                'overall_score' =>
                    $overall_score,

                'created_by' =>
                    auth()->id(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | UPDATE RANKINGS
        |--------------------------------------------------------------------------
        */

        $scores = FinanceBranchScore::where(
            'business_id',
            $business_id
        )
        ->orderByDesc('overall_score')
        ->get();

        $rank = 1;

        foreach ($scores as $score) {

            $score->update([
                'ranking_position' => $rank
            ]);

            $rank++;
        }

        return true;
    }
}