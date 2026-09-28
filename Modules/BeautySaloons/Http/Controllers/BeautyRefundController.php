<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BeautyRefundController extends Controller
{
    public function store(Request $request)
    {
        return response()->json(['success' => true, 'msg' => __('beautysaloons::bs014.refund_recorded')]);
    }
}
