<?php
namespace Modules\BankingCoreDeposits\Services;
use Illuminate\Support\Facades\DB; use Modules\BankingCoreDeposits\Entities\DepositAccount;
class DepositAccountService { public function open(array $data): DepositAccount { return DB::transaction(function () use ($data) { $data['status'] = $data['status'] ?? 'active'; $data['opened_on'] = $data['opened_on'] ?? now()->toDateString(); return DepositAccount::create($data); }); } public function changeStatus(DepositAccount $account, string $status, ?string $remarks = null): DepositAccount { $account->status = $status; if ($status === 'closed') { $account->closed_on = now()->toDateString(); } $account->remarks = $remarks ?? $account->remarks; $account->save(); return $account; } }
