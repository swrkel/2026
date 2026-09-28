<?php
namespace Modules\EzyLaw\Services;
use Modules\EzyLaw\Entities\{LawHearing,LawTask,LawAppointment,LawReminder};
class CalendarService
{
    public function events(string $from,string $to): array
    {
        $events=[];
        foreach(LawHearing::with('matter')->whereBetween('hearing_at',[$from.' 00:00:00',$to.' 23:59:59'])->get() as $x){$events[]=['at'=>$x->hearing_at,'type'=>'Hearing','title'=>$x->purpose ?: 'Court hearing','matter'=>optional($x->matter)->matter_no,'status'=>$x->status];}
        foreach(LawTask::with('matter')->whereNotNull('due_at')->whereBetween('due_at',[$from.' 00:00:00',$to.' 23:59:59'])->get() as $x){$events[]=['at'=>$x->due_at,'type'=>'Task','title'=>$x->title,'matter'=>optional($x->matter)->matter_no,'status'=>$x->status];}
        foreach(LawAppointment::with(['client','matter'])->whereBetween('start_at',[$from.' 00:00:00',$to.' 23:59:59'])->get() as $x){$events[]=['at'=>$x->start_at,'type'=>'Appointment','title'=>$x->title,'matter'=>optional($x->matter)->matter_no,'status'=>$x->status];}
        foreach(LawReminder::with('matter')->whereBetween('remind_at',[$from.' 00:00:00',$to.' 23:59:59'])->get() as $x){$events[]=['at'=>$x->remind_at,'type'=>'Reminder','title'=>$x->title,'matter'=>optional($x->matter)->matter_no,'status'=>$x->status];}
        usort($events,function($a,$b){return $a['at']->getTimestamp()<=>$b['at']->getTimestamp();});
        return $events;
    }
}
