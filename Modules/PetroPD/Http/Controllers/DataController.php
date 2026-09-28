<?php

namespace Modules\PetroPD\Http\Controllers;

use Illuminate\Routing\Controller;

class DataController extends Controller
{
    /**
     * Defines user permissions for the module.
     * @return array
     */
    public function superadmin_package()
    {
        return [
            [
                'name' => 'petro_pd_module',
                'label' => 'Petro PD Module',
                'default' => false
            ],
            [
                'name' => 'petro_pd_pd_settlement',
                'label' => 'Petro PD - PD Settlement',
                'default' => false
            ],
            [
                'name' => 'petro_pd_pd_operators',
                'label' => 'Petro PD - PD Operators',
                'default' => false
            ],
            [
                'name' => 'petro_pd_list_pd_settlement',
                'label' => 'Petro PD - List PD Settlement',
                'default' => false
            ],
            [
                'name' => 'petro_pd_user_activity',
                'label' => 'Petro PD - User Activity',
                'default' => false
            ],
            [
                'name' => 'petro_pd_adjusted_amounts_report',
                'label' => 'Petro PD - Adjusted Amounts Report',
                'default' => true
            ],
            [
                'name' => 'petro_pd_payment_reconciliation_report',
                'label' => 'Petro PD - Payment Reconciliation Report',
                'default' => true
            ],
            [
                'name' => 'petro_pd_settings',
                'label' => 'Petro PD - Settings',
                'default' => false
            ]
        ];
    }

    public function user_permissions()
    {
        return [
            [
                'value' => 'petro_pd.access',
                'label' => 'Petro PD Access',
                'default' => false
            ],
            [
                'value' => 'petro_pd.view_operators',
                'label' => 'View Pump Operators',
                'default' => false
            ],
            [
                'value' => 'petro_pd.view_report',
                'label' => 'View Reports',
                'default' => false
            ],
            [
                'value' => 'petro_pd.view_adjusted_amounts_report',
                'label' => 'View Adjusted Amounts Report',
                'default' => true
            ],
            [
                'value' => 'petro_pd.view_payment_reconciliation_report',
                'label' => 'View Payment Reconciliation Report',
                'default' => true
            ],
            [
                'value' => 'petro_pd.list_settlement',
                'label' => 'View PD Settlement List',
                'default' => false
            ],
            [
                'value' => 'petro_pd.create_settlement',
                'label' => 'Create PD Settlement',
                'default' => false
            ],
            [
                'value' => 'petro_pd.edit_settlement',
                'label' => 'Edit PD Settlement',
                'default' => false
            ],
            [
                'value' => 'petro_pd.delete_settlement',
                'label' => 'Delete PD Settlement',
                'default' => false
            ],
            [
                'value' => 'petro_pd.manual_entry',
                'label' => 'Manual Entry in Settlement',
                'default' => false
            ],

            [
                'value' => 'petro_pd.request_amount_adjustment',
                'label' => 'Request PD Settlement Amount Adjustment',
                'default' => false
            ],
            [
                'value' => 'petro_pd.adjust_settlement_amounts',
                'label' => 'Approve and Apply PD Settlement Amount Adjustments',
                'default' => false
            ],
            [
                'value' => 'petro_pd.meter_sale_tab',
                'label' => 'Meter Sale Tab',
                'default' => false
            ],
            [
                'value' => 'petro_pd.other_sale_tab',
                'label' => 'Other Sale Tab',
                'default' => false
            ],
            [
                'value' => 'petro_pd.other_income_tab',
                'label' => 'Other Income Tab',
                'default' => false
            ],
            [
                'value' => 'petro_pd.customer_payment_tab',
                'label' => 'Customer Payment Tab',
                'default' => false
            ],
            [
                'value' => 'petro_pd.payment_tab',
                'label' => 'Payment Tab',
                'default' => false
            ],
            [
                'value' => 'petro_pd_sms_notifications',
                'label' => 'SMS Notifications',
                'default' => false
            ],
            [
                'value' => 'petro_pd_whatsapp',
                'label' => 'WhatsApp Notifications',
                'default' => false
            ],
        ];
    }
}
