<?php
namespace Modules\Audit\Services\Adapters;
class UserManagementAdapter extends AbstractTableAdapter
{
    public function users(): ?string { return $this->firstTable(['users']); }
    public function roles(): ?string { return $this->firstTable(['roles']); }
    public function assignments(): ?string { return $this->firstTable(['model_has_roles', 'user_roles']); }
}
