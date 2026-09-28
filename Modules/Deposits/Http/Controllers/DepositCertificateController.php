<?php

namespace Modules\Deposits\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Deposits\Models\DepositAccount;
use Modules\Deposits\Services\DepositNumberService;

class DepositCertificateController extends Controller
{
    public function show($id, DepositNumberService $numberService)
    {
        $account = DepositAccount::with(['product', 'parties'])->findOrFail($id);
        if (empty($account->certificate_no)) {
            $account->certificate_no = $numberService->nextCertificateNumber();
            $account->save();
        }
        return view('deposits::certificates.show', compact('account'));
    }
}
