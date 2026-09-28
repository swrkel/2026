<?php
namespace Modules\BankingMicrofinance\Services\Risk;
class RiskAssessmentService {
    public function grade(array $scores): array { $total=round(($scores['capacity']??0)+($scores['character']??0)+($scores['collateral']??0)+($scores['cashflow']??0),2); $grade=$total>=80?'A':($total>=65?'B':($total>=50?'C':'D')); return ['total_score'=>$total,'risk_grade'=>$grade,'recommendation'=>$grade==='D'?'decline':($grade==='C'?'review':'approve')]; }
}
