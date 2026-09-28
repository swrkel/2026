<?php

namespace Modules\MyHealthMembers\Http\Controllers\Portal;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\MyHealthMembers\Services\Portal\MyHealthMemberPortalService;

class MyHealthMemberPortalController extends Controller
{
    protected MyHealthMemberPortalService $service;

    public function __construct(MyHealthMemberPortalService $service)
    {
        $this->service = $service;
    }

    public function dashboard(Request $request)
    {
        return view('myhealthmembers::portal.dashboard', $this->service->dashboard($request->user()));
    }

    public function profile(Request $request)
    {
        return view('myhealthmembers::portal.profile', $this->service->profile($request->user()));
    }

    public function history(Request $request)
    {
        return view('myhealthmembers::portal.history', $this->service->history($request->user()));
    }

    public function prescriptions(Request $request)
    {
        return view('myhealthmembers::portal.prescriptions', $this->service->prescriptions($request->user()));
    }

    public function documents(Request $request)
    {
        return view('myhealthmembers::portal.documents', $this->service->documents($request->user()));
    }

    public function downloadDocument(Request $request, int $id)
    {
        $result = $this->service->downloadDocument($request->user(), $id);

        if (! $result['success']) {
            return back()->withErrors(['document' => $result['message']]);
        }

        return response()->download($result['path'], $result['filename']);
    }

    public function labs(Request $request)
    {
        return view('myhealthmembers::portal.labs', $this->service->labs($request->user()));
    }

    public function radiology(Request $request)
    {
        return view('myhealthmembers::portal.radiology', $this->service->radiology($request->user()));
    }

    public function vaccinations(Request $request)
    {
        return view('myhealthmembers::portal.vaccinations', $this->service->vaccinations($request->user()));
    }

    public function billing(Request $request)
    {
        return view('myhealthmembers::portal.billing', $this->service->billing($request->user()));
    }

    public function appointments(Request $request)
    {
        return view('myhealthmembers::portal.appointments', $this->service->appointments($request->user()));
    }

    public function requestAppointment(Request $request)
    {
        $request->validate([
            'appointment_date' => 'required|date|after_or_equal:today',
            'appointment_time' => 'nullable',
            'reason' => 'nullable|string|max:1000',
        ]);

        $result = $this->service->requestAppointment($request->user(), $request->only(['appointment_date', 'appointment_time', 'reason']));

        if (! $result['success']) {
            return back()->withErrors(['appointment' => $result['message']])->withInput();
        }

        return back()->with('status', $result['message']);
    }

    public function timeline(Request $request)
    {
        return view('myhealthmembers::portal.timeline', $this->service->timeline($request->user()));
    }

    public function notifications(Request $request)
    {
        return view('myhealthmembers::portal.notifications', $this->service->notifications($request->user()));
    }

    public function settings(Request $request)
    {
        return view('myhealthmembers::portal.settings', $this->service->settings($request->user()));
    }

    public function changePasscode(Request $request)
    {
        $request->validate([
            'current_passcode' => 'required|string|max:100',
            'new_passcode' => 'required|string|min:4|max:20|confirmed',
        ]);

        $result = $this->service->changePasscode($request->user(), $request->only(['current_passcode', 'new_passcode']));

        if (! $result['success']) {
            return back()->withErrors(['passcode' => $result['message']]);
        }

        return back()->with('status', $result['message']);
    }
}
