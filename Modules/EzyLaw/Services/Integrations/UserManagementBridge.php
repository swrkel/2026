<?php
namespace Modules\EzyLaw\Services\Integrations;
class UserManagementBridge
{
    public function available(): bool
    {
        return class_exists('Modules\\UserManagementNew\\Providers\\UserManagementNewServiceProvider')
            || class_exists('Modules\\UserManagement\\Providers\\UserManagementServiceProvider');
    }
    public function moduleDefinition(): array
    {
        return [
            'key'=>'ezylaw','name'=>'EzyLaw','route'=>'ezylaw.dashboard',
            'permissions'=>require __DIR__.'/../../Config/module_permissions.php',
        ];
    }
}
