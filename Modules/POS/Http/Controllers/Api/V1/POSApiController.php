<?php

namespace Modules\POS\Http\Controllers\Api\V1;

use Illuminate\Routing\Controller;

class POSApiController extends Controller
{
    public function status()
    {
        return response()->json(['module' => 'POS', 'status' => 'active']);
    }
}
