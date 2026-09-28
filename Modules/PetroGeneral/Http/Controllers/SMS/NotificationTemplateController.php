<?php

namespace Modules\PetroGeneral\Http\Controllers\SMS;

use Illuminate\Routing\Controller;

class NotificationTemplateController extends Controller
{
    public function smsTemplates()
    {
        return app('Modules\PetroGeneral\Http\Controllers\PetroNotificationTemplateController')->index();
    }

    public function whatsappTemplates()
    {
        return app('Modules\PetroGeneral\Http\Controllers\PetroWhatsAppTemplateController')->index();
    }
}
