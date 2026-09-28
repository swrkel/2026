<?php

namespace Modules\PetroGeneral\Http\Controllers\UserActivity;

use Illuminate\Routing\Controller;
use Modules\PetroGeneral\Services\UserActivity\UserActivityPageService;

class ActivityListController extends Controller
{
    public function index(UserActivityPageService $service)
    {
        $businessId = (int) session('business.id');
        $data = $service->getIndexData($businessId);

        return view('petrogeneral::user_activity.index', $data);
    }
}
