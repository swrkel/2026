<?php
namespace Modules\LeadsNew\Policies;
class LeadsNewOpportunityPolicy { public function viewAny($user){ return $user->can('leads_new.view') || $user->can('leads_new.opportunities'); } public function update($user,$model){ return $user->can('leads_new.edit'); } }
