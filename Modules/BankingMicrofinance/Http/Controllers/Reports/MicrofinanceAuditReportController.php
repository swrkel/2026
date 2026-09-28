<?php
namespace Modules\BankingMicrofinance\Http\Controllers\Reports;
use Illuminate\Routing\Controller;use Modules\BankingMicrofinance\Entities\MicrofinanceAuditEvent;
class MicrofinanceAuditReportController extends Controller{public function index(){ $events=MicrofinanceAuditEvent::latest()->paginate(50); return view('bankingmicrofinance::reports.audit_exceptions.index',compact('events'));}}
