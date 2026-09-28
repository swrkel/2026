<?php

namespace Modules\PetroGeneral\Http\Controllers\SMS;

use Illuminate\Routing\Controller;
use Modules\PetroGeneral\Services\SMS\SmsNotificationPageService;

class NotificationListController extends Controller
{
    public function index(SmsNotificationPageService $service)
    {
        // Keep the same permission protection used by the original SMS and
        // WhatsApp template controllers. The standalone combined page must not
        // bypass that established permission.
        if (! auth()->user()->can('petro_sms_notification')) {
            abort(403, 'Unauthorized action.');
        }

        return view('petrogeneral::sms_notifications.index', $service->getIndexData());
    }
}
