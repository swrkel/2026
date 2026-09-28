<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CommonReportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Common Stock Summary
    |--------------------------------------------------------------------------
    */

    public function getStockSummary()
    {
        return response()->json([

            'success' => true,

            'message' =>
                'Common Stock Summary placeholder controller working.'

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Outstanding Report
    |--------------------------------------------------------------------------
    */

    public function getCustomerOutstandingReport()
    {
        return response()->json([

            'success' => true,

            'message' =>
                'Customer Outstanding Report placeholder controller working.'

        ]);
    }
}