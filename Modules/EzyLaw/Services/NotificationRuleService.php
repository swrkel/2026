<?php
namespace Modules\EzyLaw\Services;

use Modules\EzyLaw\Entities\{LawNotificationRule,LawNotification,LawDeadline,LawHearing,LawTask};

class NotificationRuleService
{
    public function generate(): int
    {
        $created=0;
        foreach(LawNotificationRule::where('active',1)->get() as $rule){
            $from=now()->copy()->addDays((int)$rule->days_before)->startOfDay(); $to=$from->copy()->endOfDay();
            $items=[];
            if($rule->event_key==='deadline_due') $items=LawDeadline::where('business_id',$rule->business_id)->where('status','open')->whereBetween('due_at',[$from,$to])->get();
            elseif($rule->event_key==='hearing_due') $items=LawHearing::where('business_id',$rule->business_id)->where('status','scheduled')->whereBetween('hearing_at',[$from,$to])->get();
            elseif($rule->event_key==='task_due') $items=LawTask::where('business_id',$rule->business_id)->whereNotIn('status',['completed','cancelled'])->whereBetween('due_at',[$from,$to])->get();
            foreach($items as $item){
                $sourceType='notification_rule_'.$rule->id.'_'.$rule->event_key;
                $userId=$rule->user_id;
                if(!$userId && $rule->recipient_type==='assigned_user') $userId=$item->assigned_user_id??null;
                if(!$userId && $rule->recipient_type==='responsible_user') $userId=$item->responsible_lawyer_id??$item->assigned_user_id??null;
                $clientId=$rule->recipient_type==='client' ? ($item->client_id??optional($item->matter)->client_id) : null;
                $exists=LawNotification::where('business_id',$rule->business_id)->where('source_type',$sourceType)->where('source_id',$item->id)->where('channel',$rule->channel)->where('user_id',$userId)->where('client_id',$clientId)->exists();
                if($exists) continue;
                $title=$rule->name; $body='EzyLaw '.$rule->event_key.' reminder for #'.$item->id.'.';
                LawNotification::create(['business_id'=>$rule->business_id,'user_id'=>$userId,'client_id'=>$clientId,'matter_id'=>$item->matter_id??null,'type'=>'rule','title'=>$title,'body'=>$body,'channel'=>$rule->channel,'status'=>'pending','scheduled_at'=>now(),'source_type'=>$sourceType,'source_id'=>$item->id]);
                $created++;
            }
        }
        return $created;
    }
}
