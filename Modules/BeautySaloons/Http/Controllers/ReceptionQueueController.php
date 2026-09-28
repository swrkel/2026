<?php

namespace Modules\BeautySaloons\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\BeautySaloons\Entities\BeautyReceptionQueue;
use Modules\BeautySaloons\Services\ReceptionQueueService;

class ReceptionQueueController extends Controller
{
    public function __construct(private ReceptionQueueService $queueService) {}

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $businessId = session('business.id');
            $rows = BeautyReceptionQueue::with('services')
                ->where('business_id', $businessId)
                ->when($request->business_location_id, fn ($q) => $q->where('business_location_id', $request->business_location_id))
                ->when($request->status, fn ($q) => $q->where('status', $request->status))
                ->orderByRaw("FIELD(priority, 'urgent', 'vip', 'senior', 'normal')")
                ->orderBy('arrival_at')
                ->get();

            return response()->json(['data' => $rows]);
        }

        return view('beautysaloons::reception.index');
    }

    public function create()
    {
        return view('beautysaloons::reception.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|integer',
            'appointment_id' => 'nullable|integer',
            'customer_name' => 'nullable|string|max:191',
            'mobile' => 'nullable|string|max:50',
            'visit_type' => 'nullable|string',
            'priority' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $data['business_id'] = session('business.id');
        $data['business_location_id'] = $request->input('business_location_id');
        $data['created_by'] = auth()->id();

        $queue = $this->queueService->createQueue($data);

        return redirect()->route('beautysaloons.reception.index')->with('status', [
            'success' => 1,
            'msg' => __('beautysaloons::reception.queue_created') . ' ' . $queue->queue_no,
        ]);
    }

    public function checkIn(BeautyReceptionQueue $queue)
    {
        $this->queueService->checkIn($queue);
        return response()->json(['success' => true, 'msg' => __('beautysaloons::reception.checked_in')]);
    }

    public function startService(Request $request, BeautyReceptionQueue $queue)
    {
        $this->queueService->startService($queue, $request->staff_id, $request->resource_id);
        return response()->json(['success' => true, 'msg' => __('beautysaloons::reception.service_started')]);
    }

    public function complete(BeautyReceptionQueue $queue)
    {
        $this->queueService->complete($queue);
        return response()->json(['success' => true, 'msg' => __('beautysaloons::reception.completed')]);
    }
}
