<?php
namespace Modules\BankingMicrofinance\Services\Pricing;
use Illuminate\Support\Facades\DB;
class LoanPricingService {
    public function quote(int $businessId, ?string $productCode, ?string $category, ?string $riskGrade, float $amount): ?object {
        return DB::table('bkg_mfi_loan_pricing_rules')->where('business_id',$businessId)->where('is_active',1)
            ->when($productCode,fn($q)=>$q->where(function($x)use($productCode){$x->whereNull('product_code')->orWhere('product_code',$productCode);}))
            ->when($category,fn($q)=>$q->where(function($x)use($category){$x->whereNull('customer_category')->orWhere('customer_category',$category);}))
            ->when($riskGrade,fn($q)=>$q->where(function($x)use($riskGrade){$x->whereNull('risk_grade')->orWhere('risk_grade',$riskGrade);}))
            ->where('min_amount','<=',$amount)->where(function($q)use($amount){$q->where('max_amount','>=',$amount)->orWhere('max_amount',0);})
            ->orderByDesc('risk_grade')->orderByDesc('product_code')->first();
    }
}
