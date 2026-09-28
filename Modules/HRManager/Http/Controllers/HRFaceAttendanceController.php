<?php

namespace Modules\HRManager\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\HRManager\Models\HREmployee;
use Modules\HRManager\Services\HRAttendanceService;
use Modules\HRManager\Services\HRFaceRecognitionService;

class HRFaceAttendanceController extends Controller
{
    public function kiosk()
    {
        return view('hrmanager::attendance.face-kiosk');
    }

    public function registerFace(Request $request, HRFaceRecognitionService $faceService)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'template_payload' => 'required|array',
        ]);
        $employee = HREmployee::findOrFail($data['employee_id']);
        $faceService->registerTemplate((int)($employee->business_id ?? 0), $employee->id, $data['template_payload'], Auth::id());
        return response()->json(['success' => true, 'message' => 'Face profile registered successfully.']);
    }

    public function punch(Request $request, HRFaceRecognitionService $faceService, HRAttendanceService $attendanceService)
    {
        $data = $request->validate([
            'employee_id' => 'required|integer',
            'punch_type' => 'required|in:sign_in,sign_out',
            'template_payload' => 'required|array',
            'latitude' => 'nullable',
            'longitude' => 'nullable',
        ]);
        $employee = HREmployee::with('faceProfile')->findOrFail($data['employee_id']);
        abort_if(!$employee->faceProfile, 422, 'Face profile is not registered for this employee.');
        $result = $faceService->verify($data['template_payload'], $employee->faceProfile);
        abort_if(!$result['matched'], 422, 'Face verification failed.');
        $attendance = $attendanceService->punch([
            'business_id' => $employee->business_id,
            'location_id' => $employee->location_id,
            'employee_id' => $employee->id,
            'punch_type' => $data['punch_type'],
            'source' => 'face_kiosk',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'match_score' => $result['score'],
        ]);
        return response()->json(['success' => true, 'message' => 'Attendance recorded.', 'attendance_id' => $attendance->id]);
    }
}
