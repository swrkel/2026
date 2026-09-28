<?php
namespace Modules\BankingMicrofinanceTreasury\Services;
class TreasuryNumberService{public function next(string $prefix='TRF'): string{return $prefix.'-'.date('Ymd').'-'.str_pad((string)random_int(1,99999),5,'0',STR_PAD_LEFT);}}
