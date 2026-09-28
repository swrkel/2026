<?php

namespace Modules\DigitalWallet\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\DigitalWallet\Entities\DigitalWalletApprovalRequest;

class DigitalWalletApprovalController extends Controller
{
    public function index()
    {
        $approvals = DigitalWalletApprovalRequest::latest()->paginate(50);
        return view('digitalwallet::approvals.index', compact('approvals'));
    }

    public function approve(Request $request, DigitalWalletApprovalRequest $approval)
    {
        $approval->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'reason' => $request->input('reason', $approval->reason),
        ]);

        return back()->with('status', 'Approval request approved.');
    }

    public function reject(Request $request, DigitalWalletApprovalRequest $approval)
    {
        $approval->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'reason' => $request->input('reason', $approval->reason),
        ]);

        return back()->with('status', 'Approval request rejected.');
    }
}
