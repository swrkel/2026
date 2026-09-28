<?php

namespace Modules\PetroGeneral\Http\Controllers\SMS;

use Illuminate\Routing\Controller;

class NotificationSendController extends Controller
{
    public function create()
    {
        return view('petrogeneral::sms_notifications.create');
    }
}
