<?php

namespace Modules\POS\Http\Controllers;

use Illuminate\Routing\Controller;

class PostLiveStabilizationController extends Controller
{
    public function index()
    {
        $priorityChecks = [
            ['area' => 'Routes', 'check' => 'Confirm all /pos-module/* URLs open without 404 after cache clear.', 'priority' => 'High'],
            ['area' => 'UI', 'check' => 'Compare Dashboard, Sales, Products, Registers, Returns, Reports and Settings against Communication Hub style.', 'priority' => 'High'],
            ['area' => 'Sales', 'check' => 'Complete cash, card, mixed and credit sale from Sales Workspace.', 'priority' => 'High'],
            ['area' => 'Customers', 'check' => 'Verify credit sale ledger and statement posting in standalone Customers module.', 'priority' => 'High'],
            ['area' => 'Inventory', 'check' => 'Verify stock increases on purchase, reduces on sale, restores on return and adjusts on exchange.', 'priority' => 'High'],
            ['area' => 'Registers', 'check' => 'Open/close register and reconcile cash/card/credit/refund totals.', 'priority' => 'High'],
            ['area' => 'Receipts', 'check' => 'Print 58mm, 80mm and A4 receipt/invoice and adjust only if needed per printer.', 'priority' => 'Medium'],
            ['area' => 'Reports', 'check' => 'Verify report totals against sales/register/stock transaction records.', 'priority' => 'High'],
            ['area' => 'Permissions', 'check' => 'Check cashier, manager and admin access for void, return, discount and price override.', 'priority' => 'High'],
            ['area' => 'Performance', 'check' => 'Test product search and sales list with larger data volumes.', 'priority' => 'Medium'],
        ];

        $issueTemplate = [
            'URL / Page',
            'User role used',
            'Steps to reproduce',
            'Expected result',
            'Actual result',
            'Screenshot / Laravel log',
            'Business impact',
            'Priority',
        ];

        return view('pos::support.post_live_stabilization', compact('priorityChecks', 'issueTemplate'));
    }
}
