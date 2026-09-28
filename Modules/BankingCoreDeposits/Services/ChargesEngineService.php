<?php
namespace Modules\BankingCoreDeposits\Services;
use Modules\BankingCoreDeposits\Entities\DepositCharge;
class ChargesEngineService { public function addCharge(array $data): DepositCharge { return DepositCharge::create($data); } }
