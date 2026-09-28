<?php
namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;

class BeautyInventoryReportController extends Controller
{
    public function stock() { return view('beautysaloons::reports.inventory_stock'); }
    public function productSales() { return view('beautysaloons::reports.product_sales'); }
    public function expiry() { return view('beautysaloons::reports.expiry'); }
    public function profitability() { return view('beautysaloons::reports.profitability'); }
}
