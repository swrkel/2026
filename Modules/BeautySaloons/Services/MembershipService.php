<?php

namespace Modules\BeautySaloons\Services;

use Modules\BeautySaloons\Entities\BeautyMembershipPlan;

class MembershipService
{
    public function indexData(): array { return ['plans' => BeautyMembershipPlan::latest()->paginate(25)]; }
    public function editData($id): array { return ['plan' => BeautyMembershipPlan::findOrFail($id)]; }
    public function store(array $data): BeautyMembershipPlan { return BeautyMembershipPlan::create($data); }
    public function update($id, array $data): BeautyMembershipPlan { $plan = BeautyMembershipPlan::findOrFail($id); $plan->update($data); return $plan; }
    public function reportData(): array { return ['plans' => BeautyMembershipPlan::latest()->get()]; }
}
