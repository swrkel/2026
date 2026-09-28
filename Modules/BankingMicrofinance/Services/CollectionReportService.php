<?php
namespace Modules\BankingMicrofinance\Services;
use Modules\BankingMicrofinance\Entities\CollectionCase;
class CollectionReportService {
 public function dashboard(): array { return ['open_cases'=>CollectionCase::where('status','open')->count(),'critical_cases'=>CollectionCase::where('priority','critical')->count(),'arrears_amount'=>CollectionCase::sum('arrears_amount'),'npl_cases'=>CollectionCase::whereIn('bucket',['npl_90','loss'])->count()]; }
 public function bucketSummary(){ return CollectionCase::selectRaw('bucket, count(*) as cases, sum(arrears_amount) as arrears')->groupBy('bucket')->orderBy('bucket')->get(); }
}
