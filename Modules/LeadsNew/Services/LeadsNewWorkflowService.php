<?php
namespace Modules\LeadsNew\Services;
use Modules\LeadsNew\Models\LeadsNewWorkflow;
class LeadsNewWorkflowService {
    public function run(string $event, array $payload=[]): int {
        $count=0;
        $workflows=LeadsNewWorkflow::where('business_id', session('business.id'))->where('trigger_event',$event)->where('is_active',1)->orderBy('priority')->get();
        foreach($workflows as $workflow){ $this->executeActions($workflow->actions ?? [], $payload); $count++; }
        return $count;
    }
    protected function executeActions(array $actions, array $payload): void { foreach($actions as $action){ /* standalone action dispatcher hook */ } }
}
