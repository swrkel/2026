<?php

namespace Modules\Customers\Http\Controllers;

class CustomerInfoController extends CustomerActionBaseController
{
    public function show($id)
    {
        $this->permissionService->authorize('view');
        $customer = $this->getCustomer($id);
        return view('customers::info.show', compact('customer'));
    }
}
