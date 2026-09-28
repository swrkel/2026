<?php
namespace Modules\BankingMicrofinance\Services\Financial;
class CashflowAnalysisService {
    public function calculate(array $data): array {
        $income=(float)($data['monthly_income']??0)+(float)($data['business_income']??0);
        $expenses=(float)($data['household_expenses']??0)+(float)($data['business_expenses']??0)+(float)($data['existing_debt_payments']??0);
        $installment=(float)($data['proposed_installment']??0);
        $disposable=$income-$expenses;
        $dsr=$income>0?(($installment+(float)($data['existing_debt_payments']??0))/$income)*100:0;
        return ['disposable_income'=>round($disposable,4),'debt_service_ratio'=>round($dsr,4),'affordability_status'=>($disposable>=$installment && $dsr<=60)?'affordable':'review'];
    }
}
