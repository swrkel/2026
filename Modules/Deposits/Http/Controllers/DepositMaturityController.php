<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Services\DepositLifecycleService;

class DepositMaturityController extends Controller
{
    public function due()
    {
        $accounts = Schema::hasTable('deposit_accounts')
            ? DepositAccount::where('status', 'active')->whereNotNull('maturity_on')->whereDate('maturity_on', '<=', date('Y-m-d'))->latest()->paginate(30)
            : collect();
        return view('deposits::maturity.due', compact('accounts'));
    }

    public function close(Request $request, $id, DepositLifecycleService $service)
    {
        $data = $request->validate(['closed_on' => 'nullable|date', 'notes' => 'nullable|string']);
        $service->close(DepositAccount::findOrFail($id), $data['closed_on'] ?? null, $data['notes'] ?? null);
        return back()->with('status', ['success' => 1, 'msg' => 'Deposit closed successfully.']);
    }

    public function renew(Request $request, $id, DepositLifecycleService $service)
    {
        $data = $request->validate(['maturity_on' => 'nullable|date']);
        $new = $service->renew(DepositAccount::findOrFail($id), $data['maturity_on'] ?? null);
        return redirect()->route('deposits.accounts.show', $new->id)->with('status', ['success' => 1, 'msg' => 'Deposit renewed successfully.']);
    }
}
