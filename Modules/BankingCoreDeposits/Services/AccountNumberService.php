<?php
namespace Modules\BankingCoreDeposits\Services;
class AccountNumberService { public function next(string $type, ?int $businessId = null): string { $prefix = ['savings'=>'SAV','current'=>'CUR','fixed_deposit'=>'FD'][$type] ?? 'DEP'; return $prefix . '-' . now()->format('ymdHis') . '-' . random_int(100,999); } }
