<?php
namespace Modules\DistributionNew\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\DistributionNew\Models\DisnewApprovalRequest;
use Modules\DistributionNew\Services\DisnewApprovalService;

class DisnewApprovalController extends Controller
{
    public function index()
    {
        $requests = DisnewApprovalRequest::latest()->paginate(25);
        return view('distributionnew::approvals.index', compact('requests'));
    }

    public function approve($id, DisnewApprovalService $service)
    {
        $service->approve(DisnewApprovalRequest::findOrFail($id), auth()->id(), request('remarks'));
        return back()->with('status', __('distributionnew::lang.approved_successfully'));
    }
}
