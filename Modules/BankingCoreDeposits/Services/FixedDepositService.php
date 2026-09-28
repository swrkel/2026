<?php
namespace Modules\BankingCoreDeposits\Services;
use Carbon\Carbon; use Modules\BankingCoreDeposits\Entities\FixedDeposit;
class FixedDepositService { public function open(array $data): FixedDeposit { $data['maturity_date'] = $data['maturity_date'] ?? Carbon::parse($data['start_date'] ?? now())->addMonths((int)$data['term_months'])->toDateString(); $data['fd_no'] = $data['fd_no'] ?? 'FD-'.now()->format('ymdHis').'-'.random_int(100,999); return FixedDeposit::create($data); } }
