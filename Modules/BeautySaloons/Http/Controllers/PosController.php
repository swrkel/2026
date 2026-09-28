<?php

namespace Modules\BeautySaloons\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautySale;
use Modules\BeautySaloons\Services\BeautyPosService;

class PosController extends Controller
{
    public function create()
    {
        return view('beautysaloons::pos.create');
    }

    public function store(BeautyPosService $service)
    {
        $sale = $service->createSale(request()->all());
        return redirect()->route('beauty-saloons.pos.receipt', $sale->id)->with('status', ['success' => 1, 'msg' => __('beautysaloons::beautysaloons.saved_successfully')]);
    }

    public function receipt(BeautySale $sale)
    {
        return view('beautysaloons::pos.receipt', compact('sale'));
    }
}
