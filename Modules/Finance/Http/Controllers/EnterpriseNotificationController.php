<?php

namespace Modules\Finance\Http\Controllers;

use Illuminate\Routing\Controller;

use Modules\Finance\Entities\EnterpriseNotification;
use Modules\Finance\Services\EnterpriseNotificationService;

class EnterpriseNotificationController extends Controller
{
    public function index()
    {
        $business_id = session('business.id');

        $notifications = EnterpriseNotification::where(
            'business_id',
            $business_id
        )
        ->with(['location', 'assignedTo'])
        ->latest()
        ->paginate(25);

        return view(
            'finance::enterprise_notifications.index',
            compact('notifications')
        );
    }

    public function generate()
    {
        $business_id = session('business.id');

        $service = new EnterpriseNotificationService();

        $service->generate($business_id);

        return redirect()
            ->back()
            ->with('status', [
                'success' => 1,
                'msg' => 'Enterprise notifications generated successfully.'
            ]);
    }
}