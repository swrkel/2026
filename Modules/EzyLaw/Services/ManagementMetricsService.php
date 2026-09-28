<?php
namespace Modules\EzyLaw\Services;

use Modules\EzyLaw\Entities\{LawMatter,LawInvoice,LawPayment,LawExpense,LawTimeEntry,LawClientAdvance,LawTrustAccount,LawTrustReconciliation,LawLawyerTarget};

class ManagementMetricsService
{
    public function dashboard(string $from,string $to): array
    {
        $invoices=LawInvoice::whereBetween('invoice_date',[$from,$to])->get();
        $payments=LawPayment::whereBetween('payment_date',[$from,$to])->get();
        $expenses=LawExpense::whereBetween('expense_date',[$from,$to])->get();
        return [
            'from'=>$from,'to'=>$to,'billing'=>(float)$invoices->sum('total'),'collections'=>(float)$payments->sum('amount'),'direct_expenses'=>(float)$expenses->sum('amount'),
            'receivables'=>(float)LawInvoice::sum('balance'),'wip_time'=>(float)LawTimeEntry::where('billable',1)->where('invoiced',0)->sum('amount'),
            'wip_expenses'=>(float)LawExpense::where('billable',1)->where('invoiced',0)->sum('amount'),'client_advances'=>(float)LawClientAdvance::sum('balance'),
            'trust_balance'=>(float)LawTrustAccount::where('active',1)->sum('current_balance'),'open_trust_recs'=>LawTrustReconciliation::where('status','open')->count(),
            'matter_status'=>LawMatter::selectRaw('status, COUNT(*) total')->groupBy('status')->pluck('total','status'),
            'profitability'=>$this->matterProfitability($from,$to),'lawyers'=>$this->lawyerPerformance($from,$to),
        ];
    }
    public function matterProfitability(string $from,string $to): array
    {
        $rows=[]; $matters=LawMatter::with('client')->orderBy('matter_no')->get();
        foreach($matters as $m){
            $revenue=(float)LawInvoice::where('matter_id',$m->id)->whereBetween('invoice_date',[$from,$to])->sum('total');
            $direct=(float)LawExpense::where('matter_id',$m->id)->whereBetween('expense_date',[$from,$to])->sum('amount');
            $labor=0.0;
            foreach(LawTimeEntry::where('matter_id',$m->id)->whereBetween('work_date',[$from,$to])->get() as $t){
                $target=LawLawyerTarget::where('user_id',$t->user_id)->where('period_start','<=',$t->work_date)->where('period_end','>=',$t->work_date)->orderByDesc('period_start')->first();
                $labor += ($t->minutes/60)*(float)($target->cost_rate??0);
            }
            if($revenue||$direct||$labor)$rows[]=['matter'=>$m,'revenue'=>$revenue,'expenses'=>$direct,'labor_cost'=>$labor,'profit'=>$revenue-$direct-$labor];
        }
        usort($rows,fn($a,$b)=>$b['revenue']<=>$a['revenue']); return array_slice($rows,0,50);
    }
    public function lawyerPerformance(string $from,string $to): array
    {
        $ids=LawTimeEntry::whereBetween('work_date',[$from,$to])->pluck('user_id')->merge(LawMatter::whereBetween('opened_on',[$from,$to])->pluck('responsible_lawyer_id'))->filter()->unique();
        $rows=[]; foreach($ids as $id){
            $minutes=(int)LawTimeEntry::where('user_id',$id)->whereBetween('work_date',[$from,$to])->sum('minutes');
            $billing=(float)LawTimeEntry::where('user_id',$id)->whereBetween('work_date',[$from,$to])->sum('amount');
            $newMatters=LawMatter::where('responsible_lawyer_id',$id)->whereBetween('opened_on',[$from,$to])->count();
            $target=LawLawyerTarget::where('user_id',$id)->where('period_start','<=',$to)->where('period_end','>=',$from)->orderByDesc('period_start')->first();
            $rows[]=['user_id'=>(int)$id,'hours'=>round($minutes/60,2),'billing'=>$billing,'new_matters'=>$newMatters,'target'=>$target];
        } return $rows;
    }
}
