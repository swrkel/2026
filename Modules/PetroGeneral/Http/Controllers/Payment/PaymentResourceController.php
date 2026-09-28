<?php

namespace Modules\PetroGeneral\Http\Controllers\Payment;

use Illuminate\Http\Request;

class PaymentResourceController extends BasePaymentController
{
    public function index()
    {
        return parent::index();
    }

    public function create()
    {
        return parent::create();
    }

    public function store(Request $request)
    {
        return parent::store($request);
    }

    public function show($id)
    {
        return parent::show($id);
    }

    public function edit($id)
    {
        return parent::edit($id);
    }

    public function update(Request $request, $id)
    {
        return parent::update($request, $id);
    }

    public function destroy($id)
    {
        return parent::destroy($id);
    }
}
