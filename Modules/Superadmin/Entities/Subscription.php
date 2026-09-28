<?php

namespace Modules\Superadmin\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Support\Facades\DB;

class Subscription extends Model
{
    use SoftDeletes;

    protected $connection = 'system';

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'package_details' => 'array',
        'customer_credit_notification_type' => 'array'
    ];
    
    public function getCustomerCreditNotificationTypeAttribute($value)
    {
        if (is_null($value) || $value == 'null' || $value == '"null"'  || $value == "'null'") {
            return [];
        }
        
        return $value;
    }
    

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Scope a query to only include approved subscriptions.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
    * Get the package that belongs to the subscription.
    */
    public function package()
    {
        return $this->belongsTo('\Modules\Superadmin\Entities\Package')
            ->withTrashed();
    }

    /**
     * Returns the active subscription details for a business
     *
     * @param $business_id int
     *
     * @return Response
     */
    public static function active_subscription($business_id)
    {
        $date_today = \Carbon::today()->toDateString();

        $subscription = Subscription::where('business_id', $business_id)
                            ->whereDate('start_date', '<=', $date_today)
                            ->where(function ($query) use ($date_today) {
                                $query->whereDate('end_date', '>=', $date_today)
                                      ->orWhereNull('end_date');
                            })
                            ->approved()
                            ->orderByDesc('end_date')
                            ->orderByDesc('start_date')
                            ->orderByDesc('id')
                            ->first();

        return $subscription;
    }
    
    public static function current_subscription($business_id)
    {
        $date_today = \Carbon::today()->toDateString();
        $base_query = Subscription::where('business_id', $business_id)
                            ->whereDate('start_date', '<=', $date_today)
                            ->approved();

        // Prefer the currently active approved subscription when multiple rows
        // exist for the same business. If none is active, fall back to the
        // latest approved row for compatibility with older flows.
        $active_subscription = (clone $base_query)
                            ->where(function ($query) use ($date_today) {
                                $query->whereDate('end_date', '>=', $date_today)
                                      ->orWhereNull('end_date');
                            })
                            ->orderByDesc('end_date')
                            ->orderByDesc('start_date')
                            ->orderByDesc('id')
                            ->first();

        if (!empty($active_subscription)) {
            return $active_subscription;
        }

        return $base_query
                    ->orderByDesc('end_date')
                    ->orderByDesc('start_date')
                    ->orderByDesc('id')
                    ->first();
    }

    public static function expired_grace_period_details($business_id, $grace_months = 3)
    {
        $today = \Carbon\Carbon::today();

        if (empty($business_id) || !empty(self::active_subscription($business_id))) {
            return null;
        }

        $subscription = Subscription::where('business_id', $business_id)
            ->whereDate('start_date', '<=', $today->toDateString())
            ->whereNotNull('end_date')
            ->approved()
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->first();

        if (empty($subscription) || empty($subscription->end_date)) {
            return null;
        }

        $expired_on = \Carbon\Carbon::parse($subscription->end_date)->endOfDay();

        if ($expired_on->greaterThanOrEqualTo($today)) {
            return null;
        }

        $delete_on = (clone $expired_on)->addMonths($grace_months);

        if ($today->greaterThan($delete_on)) {
            return null;
        }

        return [
            'subscription' => $subscription,
            'expired_on' => $expired_on->toDateString(),
            'delete_on' => $delete_on->toDateString(),
            'delete_on_formatted' => $delete_on->format('Y-m-d'),
            'grace_months' => $grace_months,
            'message' => 'Since the subscription is expired, will auto delete all the entered details by ' . $delete_on->format('Y-m-d') . ' date and Account will be removed. Please renew your Account to enter details from the renewal date',
        ];
    }

    public static function is_in_expired_grace_period($business_id, $grace_months = 3)
    {
        return !empty(self::expired_grace_period_details($business_id, $grace_months));
    }

    /**
     * Returns the upcoming subscription details for a business
     *
     * @param $business_id int
     *
     * @return Response
     */
    public static function upcoming_subscriptions($business_id)
    {
        $date_today = \Carbon::today();

        $subscription = Subscription::where('business_id', $business_id)
                            ->whereDate('start_date', '>', $date_today)
                            ->approved()
                            ->get();

        return $subscription;
    }

    /**
     * Returns the subscriptions waiting for approval for superadmin
     *
     * @param $business_id int
     *
     * @return Response
     */
    public static function waiting_approval($business_id)
    {
        $subscriptions = Subscription::where('business_id', $business_id)
                            ->whereNull('start_date')
                            ->waiting()
                            ->get();

        return $subscriptions;
    }

    public static function end_date($business_id)
    {
        $date_today = \Carbon::today();

        $subscription = Subscription::where('business_id', $business_id)
                            ->approved()
                            ->select(DB::raw("MAX(end_date) as end_date"))
                            ->first();
        if (empty($subscription->end_date)) {
            return $date_today;
        } else {
            return \Carbon\Carbon::parse($subscription->end_date)->addDay();
        }
    }
    public static function remaning_days($business_id)
    {
        $date_today = \Carbon::today();

        $end_date = Subscription::end_date($business_id);

        $diff = $end_date->diffInDays($date_today);

        return $diff;
    }

    /**
     * Returns the list of packages status
     *
     * @return array
     */
    public static function package_subscription_status()
    {
        return ['approved' => trans("superadmin::lang.approved"), 'declined' => trans("superadmin::lang.declined"), 'waiting' => trans("superadmin::lang.waiting")];
    }

    /**
     * Get the created_by.
     */
    public function created_user()
    {
        return $this->belongsTo(\App\User::class, 'created_id');
    }

    /**
     * Get the subscription business relationship.
     */
    public function business()
    {
        return $this->belongsTo(\App\Business::class, 'business_id');
    }

    /**
     * Get the business permission array.
     */
    public static function getBusinessPermissionsArray()
    {
        return [
            'mf_module',
            'agent_module',

            /*
             * IS1962: Pumper Dashboard / Payments / Card.
             *
             * When on, a slip number already used for a card payment in this
             * business is refused. When off, duplicates are allowed - some sites
             * legitimately reuse slip numbers across terminals.
             */
            'do_not_allow_duplicate_slip_no',
            'access_account',
            'access_sms_settings',
            'access_module',
            'hospital_system',
            'enable_restaurant',
            'enable_booking',
            'enable_crm',
            'hr_module',
            'enable_duplicate_invoice',
            'enable_sms',
            'enable_sale_cmsn_agent',
            // 'sales_agent_module',
            'monthly_total_sales_volumn',
            'customer_order_own_customer',
            'customer_settings',
            'customer_order_general_customer',
            'customer_to_directly_in_panel',
            'meter_resetting',
            'tasks_management',
            'notes_page',
            'tasks_page',
            'reminder_page',
            'member_registration',
            'visitors_registration_module',
            'visitors',
            'visitors_registration',
            'visitors_registration_setting',
            'visitors_district',
            'visitors_town',
            'disable_all_other_module_vr',
            'catalogue_qr',
            'pay_excess_commission',
            'recover_shortage',
            'pump_operator_ledger',
            'commission_type',
            'mpcs_module',
            'fleet_module',
            'ezyboat_module',
            'mpcs_form_settings',
            'list_opening_values',
            'enable_petro_module',
            'petro_pd_module',
            'merge_sub_category',
            'stock_taking_approval',
            'authorized_signature',
            'backup_module',
            'enable_separate_customer_statement_no',
            'edit_customer_statement',
            'enable_cheque_writing',
            'issue_customer_bill',
            'home_dashboard',
            'contact_module',
            'contact_supplier',
            'suppliers_module',
            'suppliers_dashboard',
            'suppliers_all_suppliers',
            'suppliers_add_supplier',
            'suppliers_contact_group',
            'suppliers_import_contacts',
            'suppliers_product_mappings',
            'suppliers_payments',
            'suppliers_purchase_history',
            'suppliers_ledger',
            'suppliers_statement',
            'suppliers_aging',
            'suppliers_stock_report',
            'suppliers_issue_payment_details',
            'suppliers_reports',
            'suppliers_settings',
            'contact_customer',
            'property_module',
            'tank_dip_chart',
            'ran_module',
            'report_module',
            'verification_report',
            'monthly_report',
            'comparison_report',
            'notification_template_module',
            'list_easy_payment',
            'settings_module',
            'business_settings',
            'business_location',
            'invoice_settings',
            'settings_otp_verification',
            'settings_pay_online',
            'settings_reports_configurations',
            'settings_user_locations',
            'tax_rates',
            'user_management_module',
            'banking_module',
            'orders',
            'products',
            'products_new_module',
            'products_new_dashboard',
            'products_new_products',
            'products_new_stock_center',
            'products_new_inventory_movements',
            'products_new_opening_stock',
            'products_new_price_center',
            'products_new_barcode_center',
            'products_new_import_export',
            'products_new_reports',
            'products_new_settings',
            'purchase',
            'stock_transfer',
            'service_staff',
            'enable_subscription',
            'pos_sale',
            'status_order',
            'sale_module',
            'all_sales',
            'add_sale',
            'list_pos',
            'list_draft',
            'list_quotation',
            'list_sell_return',
            'shipment',
            'discount',
            'import_sale',
            'reserved_stock',
            'list_orders',
            'upload_orders',
            'subcriptions',
            'over_limit_sales',
            'stock_adjustment',
            'tables',
            'type_of_service',
            'expenses',
            'modifiers',
            'kitchen',
            'upload_images',
            'leads_module',
            'leads_new_module',
            'leads_new_dashboard',
            'leads_new_leads',
            'leads_new_settings',
            'leads_new_reports',
            'leads',
            'day_count',
            'leads_import',
            'leads_settings',
            'sms_module',
            'cache_clear',
            'pump_operator_dashboard',
            'list_sms',
            'employee',
            'teminated',
            'award',
            'leave_request',
            'attendance',
            'import_attendance',
            'late_and_over_time',
            'payroll',
            'salary_details',
            'basic_salary',
            'payroll_payments',
            'hr_reports',
            'attendance_report',
            'employee_report',
            'payroll_report',
            'notice_board',
            'hr_settings',
            'department',
            'jobtitle',
            'jobcategory',
            'workingdays',
            'workshift',
            'holidays',
            'leave_type',
            'salary_grade',
            'employment_status',
            'salary_component',
            'hr_prefix',
            'hr_tax',
            'religion',
            'hr_setting_page',
            'repair_module',
            'job_sheets',
            'add_job_sheet',
            'list_invoice',
            'add_invoice',
            'brands',
            'select_pump_operator_in_settlement',
            'show_mechanical_meter',
            'repair_settings',
            'customer_interest_deduct_option',
            'dsr_module',
            'discount_module',
            'tpos_module',
            'ledger_discount',
            'realize_cheque',
            'customer_statement_pmt',
            'vat_sale',
            'list_vat_sale',
            'vat_purchase',
            'list_vat_purchase',
            'vat_expense',
            'list_vat_expense',
            'vat_products',
            'vat_contacts',
            'only_walkin',
            
            'stock_conversion_module',
            'list_credit_sales_page',
            'duplicate_slip_numbers',
            'vat_meter_sales',
            'docmanagement_module',
            'vat_credit_bill',
            'vat_module',
            
            'hrm_ledger',
            'hrm_dashboard',
            'hrm_leave',
            'hrm_sales_target',
            'hrm_settings',
            'hrm_salary_details',
            
            'edit_settlement_date',
            '1_7_days',
            '8_14_days',
            '15_21_days',
            '22_30_days',
            'over_30_days',
            '1_30_days',
            '31_45_days',
            '46_60_days',
            '61_90_days',
            'over_90_days',
            'customized_vat_invoices',
            'customized_report',
            
            'vat_linked_accounts',
            
            'post_dated_cheque',
            'show_post_dated_cheque',
            'update_post_dated_cheque',
            
            // new product related permissions
            'products_list_product',
            'products_all_products',
            'products_current_stock',
            'products_add_edit',
            'products_stock_history',
            'products_stock_report',
            'products_opening_stock',
            'products_variations',
            'products_import',
            'products_import_opening_stock',
            'products_selling_price_group',
            'products_units',
            'products_stock_conversion',
            'products_categories',
            'products_brand_warranties', 
            
            // price change module new permissions
            'price_change_edit_qty',
            
            'subscriptions_module',
            'list_subscriptions',
            'subscriptions_settings',
            'subscriptions_sms_template',
            'subscriptions_user_activity',
            
            'bakery_module',
            'bakery_drivers',
            'bakery_vehicles',
            'bakery_products',
            'bakery_starting_no',
            'bakery_list_products',
            'bakery_settings_show_vehicle_opening_balance',
            'um_add_role',
            // MA-002 (LA-1135): without this line the checkbox renders but the
            // tick is discarded on save - this array is what gets persisted.
            'um_add_user',
            'um_edit_role',
            'um_delete_role',
            
            'add_pd_cheque',
            
            'management_reports',
            'unfinished_form',
            'routes',
            'drivers',
            'helpers',
            'pump_operator',
            'daily_pump_status',
            'day_count',
            'access_selling_price',
            'set_minimum_price',
            'view_sales_commission',
            'current_sale',
            
            'petro_sms_notifications',
            'fleet_vat_invoice2',
            
            // individual VAT updates
            'individual_purchase',
            'individual_sale',
            'individual_expense',
            
            // Range VAT Updates
            'range_purchase',
            'range_sale',
            'range_expense',
            
            'cheque_dashboard',
            'cheque_add_template',
            'cheque_cancelled_cheques',
            'cheque_printed_cheques',
            
            'ezy_list_products',
            'ezy_units',
            'ezy_categories',
            'ezy_show_current_stock',
            'ezy_show_stock_report',
            
            'sms_ledger','sms_delivery_report','sms_history',
            'sms_quick_send','sms_from_file','sms_campaign',
            
            'edit_settlement_no_change',
            'allow_duplicate_order_numbers',

            'same_order_no_daily_collection',
            'daily_shift_page',
            'daily_cash_tab',
            'daily_credit_sales',
            'daily_cards',
            'daily_shortage_excess',
            'daily_cheques',
            'collection_summary',
            'daily_collections_settings',
            'daily_collections_settings_setting',
            'daily_cash_status',
            'daily_shift',
            
            
            'contact_list_customer_loans',
            'loan_module',
            'patient_module',
            'patient_test_module',
            'loan_show_contact_type',
            'contact_settings',
            'contact_list_supplier_map_products',
            'contact_add_supplier_products',
            'contact_import_opening_balalnces',
            'contact_returned_cheque_details',
            'product_print_labels',
            
            'contact_delete_customer_statement',
            'contact_delete_statement_payment',
            'vat_delete_customer_statement',
            'vat_delete_statement_payment',
            'enable_126_statement',
            'settlement_sw_other_income',
            'settlement_sw_customer_payments',
            'settlement_sw_expenses',
            'credit_customer_manual_bills',
            'development',
            'manual_bill',
            'list_tank_transfer',
            'dashboard_logistics',
            'daily_collection',
            'daily_collection_sw',
            'store_label_type',

            // Modified by Engr. Alex -- task 7882: Issue 7 - Customized Reports module permissions
            'customized_reports_module',
            'cr_add_lioc_statement',
            'cr_list_lioc_statements',
            'cr_edit_lioc_statement',
            'cr_prefix_numbers',
        ];
    }


}
