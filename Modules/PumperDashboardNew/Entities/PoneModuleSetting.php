<?php

namespace Modules\PumperDashboardNew\Entities;

class PoneModuleSetting extends PoneBaseModel
{
    protected $table = 'pone_module_settings';
    protected $casts = [
        'integration_enabled' => 'boolean',
        'sync_during_operation' => 'boolean',
        'require_clean_sync_before_close' => 'boolean',
        'allow_operator_open_shift' => 'boolean',
        'allow_close_with_open_pumps' => 'boolean',
        'allow_close_with_pending_sync' => 'boolean',
        'cash_denomination_enabled' => 'boolean',
        'multi_card_enabled' => 'boolean',
        'cheque_enabled' => 'boolean',
        'credit_sale_enabled' => 'boolean',
        'require_credit_dual_confirmation' => 'boolean',
        'require_collection_before_close' => 'boolean',
        'allow_payment_edit' => 'boolean',
        'settings' => 'array',
    ];
}
