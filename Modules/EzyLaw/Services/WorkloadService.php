<?php
namespace Modules\EzyLaw\Services;
use Illuminate\Support\Facades\DB;
use Modules\EzyLaw\Entities\{LawMatter,LawTask,LawHearing,LawTimeEntry,LawDeadline};
class WorkloadService {
    public function data(string $from,string $to): array {
        $ids=[];
        foreach([
            LawMatter::whereNotNull('responsible_lawyer_id')->pluck('responsible_lawyer_id')->all(),
            LawTask::whereNotNull('assigned_user_id')->pluck('assigned_user_id')->all(),
            LawTimeEntry::whereBetween('work_date',[$from,$to])->pluck('user_id')->all(),
            LawDeadline::whereNotNull('assigned_user_id')->pluck('assigned_user_id')->all()
        ] as $set){$ids=array_merge($ids,$set);} $ids=array_values(array_unique(array_filter($ids)));
        $rows=[];
        foreach($ids as $id){
            $rows[]=[
                'user_id'=>$id,
                'name'=>$this->userName((int)$id),
                'active_matters'=>LawMatter::where('responsible_lawyer_id',$id)->whereIn('status',['open','pending'])->count(),
                'open_tasks'=>LawTask::where('assigned_user_id',$id)->whereNotIn('status',['completed','cancelled'])->count(),
                'deadlines'=>LawDeadline::where('assigned_user_id',$id)->where('status','open')->whereBetween('due_at',[$from.' 00:00:00',$to.' 23:59:59'])->count(),
                'hearings'=>LawHearing::whereHas('matter',function($q)use($id){$q->where('responsible_lawyer_id',$id);})->whereBetween('hearing_at',[$from.' 00:00:00',$to.' 23:59:59'])->count(),
                'hours'=>round((float)LawTimeEntry::where('user_id',$id)->whereBetween('work_date',[$from,$to])->sum('minutes')/60,2)
            ];
        }
        usort($rows,function($a,$b){return $b['active_matters']<=>$a['active_matters'];}); return $rows;
    }
    private function userName(int $id): string {
        try { if(class_exists('\\App\\User')){$u=\App\User::find($id); if($u)return $u->first_name ? trim($u->first_name.' '.$u->last_name) : ($u->username?:'User #'.$id);} } catch(\Throwable $e){}
        return 'User #'.$id;
    }
}
