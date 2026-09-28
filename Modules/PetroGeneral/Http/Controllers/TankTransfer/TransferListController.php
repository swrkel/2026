<?php

namespace Modules\PetroGeneral\Http\Controllers\TankTransfer;

use Illuminate\Routing\Controller;
use Modules\PetroGeneral\Services\TankTransfer\TankTransferPageService;

class TransferListController extends Controller
{
    public function index(TankTransferPageService $service)
    {
        $businessId = (int) (
            session('user.business_id')
            ?: session('business.id')
        );
        abort_if($businessId <= 0, 403, 'Business context is not available.');
        $data = $service->getIndexData($businessId);

        return view('petrogeneral::tank_transfer.index', $data);
    }
}
