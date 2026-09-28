<?php

namespace Modules\Customers\Http\Controllers;

use Illuminate\Routing\Controller;
use Modules\Customers\Services\CustomerProfileService;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerProfileController extends Controller
{
    protected $profileService;
    protected $permissionService;

    public function __construct(CustomerProfileService $profileService, CustomerPermissionService $permissionService)
    {
        $this->profileService = $profileService;
        $this->permissionService = $permissionService;
    }

    public function show($id)
    {
        $this->permissionService->authorize('view');

        $businessId = (int) request()->session()->get('user.business_id');
        $data = $this->profileService->profileData($businessId, (int) $id);

        return view('customers::profile.show', $data);
    }
}
