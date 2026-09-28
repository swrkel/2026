<?php

namespace Modules\HotelManagement\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Http\Request;
use Modules\HotelManagement\Services\StaffTrainingService;

class StaffTrainingController extends Controller
{
    public function __construct(protected StaffTrainingService $service) {}

    public function index()
    {
        return view('hotelmanagement::staff_training.index', [
            'staffTraining' => $this->service->dashboard(),
        ]);
    }

    public function course(Request $request)
    {
        $this->service->course($request->validate([
            'course_code' => 'nullable|string|max:60',
            'course_name' => 'required|string|max:191',
            'department' => 'nullable|string|max:100',
            'training_type' => 'required|string|max:60',
            'validity_days' => 'nullable|integer|min:0',
            'is_mandatory' => 'nullable|boolean',
            'status' => 'nullable|string|max:40',
            'description' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Training course saved successfully.');
    }

    public function session(Request $request)
    {
        $this->service->session($request->validate([
            'course_id' => 'required|integer',
            'trainer_name' => 'nullable|string|max:191',
            'training_date' => 'required|date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'venue' => 'nullable|string|max:191',
            'capacity' => 'nullable|integer|min:0',
            'status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Training session planned successfully.');
    }

    public function assign(Request $request)
    {
        $this->service->assign($request->validate([
            'session_id' => 'required|integer',
            'staff_id' => 'required|integer',
            'attendance_status' => 'nullable|string|max:40',
            'score' => 'nullable|numeric|min:0|max:100',
            'result_status' => 'nullable|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Staff training record saved successfully.');
    }

    public function result($id, Request $request)
    {
        $this->service->result((int) $id, $request->validate([
            'attendance_status' => 'required|string|max:40',
            'score' => 'nullable|numeric|min:0|max:100',
            'result_status' => 'required|string|max:40',
            'remarks' => 'nullable|string|max:1000',
        ]), optional($request->user())->id);
        return back()->with('status', 'Training result updated successfully.');
    }
}
