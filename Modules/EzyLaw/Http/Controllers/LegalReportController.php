<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawMatter,LawDeadline,LawEvidenceItem,LawTrustReconciliation,LawInvoice,LawTimeEntry,LawTask,LawCourtFiling,LawSettlement,LawFeeEstimate,LawClientAdvance,LawEsignRequest};
class LegalReportController extends Controller {
 public function index(Request $r){$from=$r->input('from',now()->startOfMonth()->toDateString());$to=$r->input('to',now()->endOfMonth()->toDateString());return view('ezylaw::reports.legal',[
 'from'=>$from,'to'=>$to,
 'open_matters'=>LawMatter::with('client')->whereIn('status',['open','pending'])->orderBy('matter_no')->get(),
 'deadlines'=>LawDeadline::with('matter')->where('status','open')->whereBetween('due_at',[$from.' 00:00:00',$to.' 23:59:59'])->orderBy('due_at')->get(),
 'overdue_deadlines'=>LawDeadline::where('status','open')->where('due_at','<',now())->count(),
 'evidence'=>LawEvidenceItem::with('matter')->whereBetween('created_at',[$from.' 00:00:00',$to.' 23:59:59'])->get(),
 'trust_recs'=>LawTrustReconciliation::with('account')->whereBetween('statement_date',[$from,$to])->get(),
 'receivables'=>(float)LawInvoice::where('balance','>',0)->sum('balance'),
 'hours'=>round((float)LawTimeEntry::whereBetween('work_date',[$from,$to])->sum('minutes')/60,2),
 'open_tasks'=>LawTask::whereNotIn('status',['completed','cancelled'])->count(),
 'filings'=>LawCourtFiling::with(['matter','court'])->whereBetween('filed_on',[$from,$to])->orderBy('filed_on')->get(),
 'settlements'=>LawSettlement::with('matter')->where(function($q)use($from,$to){$q->whereBetween('offered_on',[$from,$to])->orWhereBetween('accepted_on',[$from,$to])->orWhereBetween('completed_on',[$from,$to]);})->get(),
 'estimates'=>LawFeeEstimate::with(['client','matter'])->whereBetween('estimate_date',[$from,$to])->get(),
 'advance_balance'=>(float)LawClientAdvance::sum('balance'),
 'pending_esign'=>LawEsignRequest::whereIn('status',['pending','viewed'])->count(),
 ]);}
}
