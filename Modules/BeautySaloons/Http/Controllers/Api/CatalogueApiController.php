<?php

namespace Modules\BeautySaloons\Http\Controllers\Api;

use Illuminate\Routing\Controller;
use Modules\BeautySaloons\Entities\BeautyService;
use Modules\BeautySaloons\Entities\BeautyStaff;
use Modules\BeautySaloons\Services\Api\BeautyPortalApiResponse;

class CatalogueApiController extends Controller
{
    public function branches() { return BeautyPortalApiResponse::success([]); }
    public function services() { return BeautyPortalApiResponse::success(BeautyService::query()->where('status', 'active')->get()); }
    public function staffAvailability() { return BeautyPortalApiResponse::success(BeautyStaff::query()->where('status', 'active')->get()); }
}
