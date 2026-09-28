<?php

namespace Modules\BeautySaloons\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\BeautySaloons\Entities\BeautyReceptionQueue;

class ReceptionDashboardController extends Controller
{
    public function index()
    {
        $businessId = session('business.id');
        $today = now()->toDateString();

        $summary = [
            'waiting' => BeautyReceptionQueue::where('business_id', $businessId)->whereDate('arrival_at', $today)->where('status', 'waiting')->count(),
            'checked_in' => BeautyReceptionQueue::where('business_id', $businessId)->whereDate('arrival_at', $today)->where('status', 'checked_in')->count(),
            'in_service' => BeautyReceptionQueue::where('business_id', $businessId)->whereDate('arrival_at', $today)->where('status', 'in_service')->count(),
            'completed' => BeautyReceptionQueue::where('business_id', $businessId)->whereDate('arrival_at', $today)->where('status', 'completed')->count(),
        ];

        return view('beautysaloons::reception.dashboard', compact('summary'));
    }
}
