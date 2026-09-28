<?php
namespace Modules\Audit\Services;

class ModuleRegistry
{
    public function rules(): array
    {
        return [
            \Modules\Audit\Rules\System\AuditSchemaRule::class,
            \Modules\Audit\Rules\System\RouteActionIntegrityRule::class,
            \Modules\Audit\Rules\System\OrphanTransactionPaymentRule::class,
            \Modules\Audit\Rules\Finance\UnbalancedAccountTransactionRule::class,
            \Modules\Audit\Rules\Finance\MissingAccountReferenceRule::class,
            \Modules\Audit\Rules\Customers\CustomerTransactionContactRule::class,
            \Modules\Audit\Rules\Suppliers\SupplierTransactionContactRule::class,
            \Modules\Audit\Rules\Inventory\NegativeStockRule::class,
            \Modules\Audit\Rules\Inventory\OrphanVariationRule::class,
            \Modules\Audit\Rules\Inventory\OrphanLocationStockRule::class,
            \Modules\Audit\Rules\UserManagement\OrphanRoleAssignmentRule::class,
            \Modules\Audit\Rules\UserManagement\InactiveUserRoleRule::class,
        ];
    }

    public function modules(): array
    {
        $out = [];
        foreach ($this->rules() as $class) {
            try {
                $rule = app($class);
                $out[$rule->module()] = $rule->module();
            } catch (\Throwable $e) {}
        }
        ksort($out);
        return array_values($out);
    }
}
