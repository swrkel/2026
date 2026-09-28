<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Routing\Controller;
class PortfolioQualityReportController extends Controller{public function index(){return view('bankingmicrofinance::reports.portfolio_quality.index',['summary'=>['portfolio'=>0,'par30'=>0,'par90'=>0,'npl'=>0,'write_off'=>0,'recovery'=>0]]);}}
