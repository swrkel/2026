<?php

namespace Modules\Customers\Policies;

use App\User;
use Modules\Customers\Services\CustomerPermissionService;

class CustomerApprovalPolicy
{
    protected CustomerPermissionService $permissionService;

    public function __construct(CustomerPermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }

    public function view(User $user): bool
    {
        return $this->permissionService->allows('view');
    }

    public function create(User $user): bool
    {
        return $this->permissionService->allows('create');
    }

    public function update(User $user): bool
    {
        return $this->permissionService->allows('edit');
    }

    public function delete(User $user): bool
    {
        return $this->permissionService->allows('delete');
    }
}
