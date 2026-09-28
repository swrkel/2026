<?php

namespace Modules\BeautySaloons\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Services\Api\BeautyPortalApiResponse;

class MembershipApiController extends Controller
{
    public function index()
    {
        return BeautyPortalApiResponse::success([]);
    }
    public function packages()
    {
        return BeautyPortalApiResponse::success([]);
    }
}
