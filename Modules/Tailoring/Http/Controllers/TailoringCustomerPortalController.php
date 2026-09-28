<?php
namespace Modules\Tailoring\Http\Controllers;
use Illuminate\Routing\Controller;
class TailoringCustomerPortalController extends Controller
{
    public function index() { return view('tailoring::customer_portal.index'); }
    public function orderTracking($id) { return view('tailoring::customer_portal.order_tracking', compact('id')); }
}
