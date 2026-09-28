<?php

namespace Modules\Finance\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Finance\Services\YearClosureService;

/**
 * Close Financial Year.
 *
 * Closing is restricted to a superadmin. It stops every user in the business
 * from entering or amending anything in the closed period, so it is not a
 * setting an ordinary administrator should be able to apply — or undo.
 */
class YearClosureController extends Controller
{
    public function __construct(protected YearClosureService $closures)
    {
    }

    protected function businessId(): int
    {
        return (int) (session('business.id') ?: session('user.business_id') ?: 0);
    }

    protected function assertAllowed(): void
    {
        abort_unless(auth()->user()->can('superadmin'), 403,
            'Only a Super Admin can close or reopen a financial year.');
    }

    public function index()
    {
        $this->assertAllowed();
        $businessId = $this->businessId();

        return view('finance::year_closure.index', [
            'closures' => $this->closures->history($businessId),
            'closedUpto' => $this->closures->closedUpto($businessId),
            'earliest' => $this->closures->earliestAllowedDate($businessId),
        ]);
    }

    public function close(Request $request)
    {
        $this->assertAllowed();

        $data = $request->validate([
            'closed_upto' => 'required|date',
            'label' => 'nullable|string|max:100',
            'note' => 'nullable|string',
            // Typing the date again is deliberate friction: this stops every
            // user in the business from touching a period, and a mis-click
            // should not be enough to do it.
            'confirm_date' => 'required|date|same:closed_upto',
        ], [
            'confirm_date.same' => 'The confirmation date does not match the closing date.',
        ]);

        $result = $this->closures->close(
            $this->businessId(),
            $data['closed_upto'],
            (int) auth()->id(),
            $data['label'] ?? null,
            $data['note'] ?? null
        );

        return redirect()->route('finance.year-closure.index')
            ->with($result['success'] ? 'status' : 'error', $result['msg']);
    }

    public function reopen(Request $request, $id)
    {
        $this->assertAllowed();

        $data = $request->validate([
            // A reason is required, not optional. Reopening a closed year is
            // the sort of act someone will be asked about later.
            'reopen_reason' => 'required|string|max:500',
        ], [
            'reopen_reason.required' => 'Give a reason for reopening the year.',
        ]);

        $result = $this->closures->reopen(
            (int) $id,
            $this->businessId(),
            (int) auth()->id(),
            $data['reopen_reason']
        );

        return redirect()->route('finance.year-closure.index')
            ->with($result['success'] ? 'status' : 'error', $result['msg']);
    }
}
