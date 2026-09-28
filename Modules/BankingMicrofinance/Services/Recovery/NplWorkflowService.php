<?php
namespace Modules\BankingMicrofinance\Services\Recovery;
use Illuminate\Support\Facades\DB;
class NplWorkflowService {
    public function stageByDays(int $days): string { return $days>=180?'legal':($days>=90?'doubtful':($days>=30?'substandard':'watch')); }
    public function dashboard(int $businessId): array { return DB::table('bkg_mfi_npl_cases')->select('stage', DB::raw('count(*) as cases'), DB::raw('sum(arrears_amount) as arrears'))->where('business_id',$businessId)->where('status','open')->groupBy('stage')->get()->toArray(); }
}
