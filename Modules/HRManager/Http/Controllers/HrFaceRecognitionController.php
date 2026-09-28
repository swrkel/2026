<?php

namespace Modules\HRManager\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\HRManager\Models\HrFaceAttendanceAttempt;
use Modules\HRManager\Models\HrFaceEnrollmentSession;
use Modules\HRManager\Services\HRFaceRecognitionService;

class HrFaceRecognitionController extends Controller
{
    protected HRFaceRecognitionService $faceService;

    public function __construct(HRFaceRecognitionService $faceService)
    {
        $this->faceService = $faceService;
    }

    public function dashboard()
    {
        $businessId = session('business.id');

        $sessions = HrFaceEnrollmentSession::where('business_id', $businessId)
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        $attempts = HrFaceAttendanceAttempt::where('business_id', $businessId)
            ->orderByDesc('attempt_time')
            ->limit(10)
            ->get();

        return view('hrmanager::face.dashboard', compact('sessions', 'attempts'));
    }

    public function enrollment()
    {
        return view('hrmanager::face.enrollment');
    }

    public function startEnrollment(Request $request)
    {
        $request->validate(['employee_id' => 'required|integer']);

        $this->faceService->recordConsent([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'ip_address' => $request->ip(),
            'user_id' => auth()->id(),
        ]);

        $session = $this->faceService->startEnrollment([
            'business_id' => session('business.id'),
            'employee_id' => $request->employee_id,
            'device_id' => $request->device_id,
            'user_id' => auth()->id(),
        ]);

        return back()->with('status', [
            'success' => 1,
            'msg' => 'Face enrollment session started: ' . $session->session_code,
        ]);
    }

    public function attempts()
    {
        $attempts = HrFaceAttendanceAttempt::where('business_id', session('business.id'))
            ->orderByDesc('attempt_time')
            ->paginate(25);

        return view('hrmanager::face.attempts', compact('attempts'));
    }
}
