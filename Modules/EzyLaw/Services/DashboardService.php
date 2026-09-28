<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\{LawClient,LawMatter,LawHearing,LawInvoice,LawTask,LawTrustAccount,LawReminder,LawRetainer,LawConflictCheck,LawDeadline,LawTrustReconciliation,LawTimeEntry,LawExpense,LawCourtFiling,LawSettlement,LawClientAdvance,LawDocumentApproval,LawEsignRequest};
class DashboardService
{
    public function data(): array{
        return [
            'clients'=>LawClient::where('status','active')->count(),
            'active_matters'=>LawMatter::whereIn('status',['open','pending'])->count(),
            'hearings_7d'=>LawHearing::whereBetween('hearing_at',[now()->startOfDay(),now()->addDays(7)->endOfDay()])->count(),
            'overdue_tasks'=>LawTask::whereNotIn('status',['completed','cancelled'])->where('due_at','<',now())->count(),
            'receivables'=>(float)LawInvoice::where('balance','>',0)->sum('balance'),
            'trust_balance'=>(float)LawTrustAccount::where('active',1)->sum('current_balance'),
            'due_reminders'=>LawReminder::where('status','pending')->where('remind_at','<=',now()->addDay())->count(),
            'active_retainers'=>LawRetainer::where('status','active')->count(),
            'conflict_reviews'=>LawConflictCheck::where('result_status','review')->count(),
            'overdue_deadlines'=>LawDeadline::where('status','open')->where('due_at','<',now())->count(),
            'trust_reconcile_open'=>LawTrustReconciliation::where('status','open')->count(),
            'unbilled_time'=>(float)LawTimeEntry::where('billable',1)->where('invoiced',0)->sum('amount'),
            'unbilled_expenses'=>(float)LawExpense::where('billable',1)->where('invoiced',0)->sum('amount'),
            'pending_filings'=>LawCourtFiling::whereIn('status',['draft','filed','returned'])->count(),
            'active_settlements'=>LawSettlement::whereIn('status',['draft','offered','accepted'])->count(),
            'client_advances'=>(float)LawClientAdvance::sum('balance'),
            'pending_approvals'=>LawDocumentApproval::where('status','pending')->count(),
            'pending_esign'=>LawEsignRequest::whereIn('status',['pending','viewed'])->count(),
            'upcoming'=>LawHearing::with('matter')->where('hearing_at','>=',now())->orderBy('hearing_at')->limit(10)->get(),
            'reminders'=>LawReminder::with('matter')->where('status','pending')->where('remind_at','>=',now()->subDay())->orderBy('remind_at')->limit(10)->get(),
        ];
    }
}
