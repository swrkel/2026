<?php
namespace Modules\EzyLaw\Http\Controllers;
use Illuminate\Http\Request; use Illuminate\Routing\Controller;
use Modules\EzyLaw\Entities\{LawMatter,LawHearing,LawTimeEntry,LawInvoice,LawExpense,LawTrustTransaction,LawRetainer,LawConflictCheck};
class ReportController extends Controller
{
 public function index(Request $r){
    $from=$r->input('from',now()->startOfMonth()->toDateString());$to=$r->input('to',now()->endOfMonth()->toDateString());
    return view('ezylaw::reports.index',[
        'from'=>$from,'to'=>$to,
        'matters'=>LawMatter::with('client')->whereBetween('opened_on',[$from,$to])->get(),
        'hearings'=>LawHearing::with('matter')->whereBetween('hearing_at',[$from.' 00:00:00',$to.' 23:59:59'])->get(),
        'minutes'=>LawTimeEntry::whereBetween('work_date',[$from,$to])->sum('minutes'),
        'billable'=>(float)LawTimeEntry::whereBetween('work_date',[$from,$to])->where('billable',1)->sum('amount'),
        'receivables'=>(float)LawInvoice::where('balance','>',0)->sum('balance'),
        'billed'=>(float)LawInvoice::whereBetween('invoice_date',[$from,$to])->sum('total'),
        'expenses'=>(float)LawExpense::whereBetween('expense_date',[$from,$to])->sum('amount'),
        'trust_in'=>(float)LawTrustTransaction::whereBetween('transaction_date',[$from,$to])->where('direction','credit')->sum('amount'),
        'trust_out'=>(float)LawTrustTransaction::whereBetween('transaction_date',[$from,$to])->where('direction','debit')->sum('amount'),
        'retainers'=>LawRetainer::whereBetween('agreement_date',[$from,$to])->count(),
        'conflicts'=>LawConflictCheck::whereBetween('checked_at',[$from.' 00:00:00',$to.' 23:59:59'])->count(),
    ]);
 }
}
