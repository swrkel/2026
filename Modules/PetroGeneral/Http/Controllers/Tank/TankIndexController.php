<?php

namespace Modules\PetroGeneral\Http\Controllers\Tank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PetroGeneral\Http\Controllers\FuelTankController;
use Modules\PetroGeneral\Http\Controllers\TankTransferController;

class TankIndexController extends Controller
{
    public function index(Request $request)
    {
        /*
         * Tank Transfers is also an internal tab of Tank Management.
         *
         * Do not make that embedded tab call the standalone
         * /tank-transfers-general page route. The global page-permission layer
         * correctly protects that standalone page with the separate
         * "List Tank Transfer" permission, which means a business that has
         * Tank Management enabled but not the standalone page receives a 403
         * inside DataTables.
         *
         * Keep the embedded tab under the already-authorized Tank Management
         * URL and dispatch only the requested internal operation here.
         */
        if ($request->boolean('petrogeneral_embedded_tank_transfer_data')) {
            return app(TankTransferController::class)->index();
        }

        if ($request->boolean('petrogeneral_embedded_tank_transfer_create')) {
            return app(TankTransferController::class)->create();
        }

        return app(FuelTankController::class)->index($request);
    }
}
