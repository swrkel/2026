<?php

namespace Modules\MyHealthMembers\Services;

use Illuminate\Support\Str;
use Modules\MyHealthMembers\Entities\MyHealthTelemedicineAppointment;
use Modules\MyHealthMembers\Entities\MyHealthTelemedicineSession;

class MyHealthTelemedicineSessionService
{
    public function openSession(MyHealthTelemedicineAppointment $appointment): MyHealthTelemedicineSession
    {
        $session = $appointment->session ?: new MyHealthTelemedicineSession(['appointment_id' => $appointment->id]);
        if (empty($session->session_token)) {
            $session->session_token = (string) Str::uuid();
        }
        $session->meeting_url = $session->meeting_url ?: route('myhealth.telemedicine.waiting-room', $appointment->id);
        $session->status = 'waiting';
        $session->save();

        $appointment->update(['status' => 'waiting']);
        return $session;
    }
}
