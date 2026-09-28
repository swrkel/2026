<?php

namespace Modules\PetroPD\Entities;

use App\Business;
use Illuminate\Database\Eloquent\Model;

class PetroPdNotificationTemplate extends Model
{
    protected $table = 'petro_notification_templates';
    protected $guarded = ['id'];

    public static function getTemplate($business_id, $template_for)
    {
        $notif_template = static::where('business_id', $business_id)
            ->where('template_for', $template_for)
            ->first();

        $business = Business::find($business_id);
        $phone_nos = '';
        $sms_settings = $business->sms_settings ?? [];
        if (is_string($sms_settings)) {
            $decoded = json_decode($sms_settings, true);
            $sms_settings = is_array($decoded) ? $decoded : [];
        }
        if (is_array($sms_settings)) {
            $phone_nos = $sms_settings['msg_phone_nos'] ?? '';
        }

        return [
            'sms_body' => !empty($notif_template->sms_body) ? $notif_template->sms_body : '',
            'template_for' => $template_for,
            'auto_send_sms' => !empty($notif_template->auto_send_sms) ? 1 : 0,
            'phone_nos' => $notif_template->phone_nos ?? $phone_nos,
        ];
    }

    public static function notifications()
    {
        return [
            'pd_settlements' => [
                'name' => 'PD Settlement',
                'extra_tags' => [
                    '{settlement_no}', '{settlement_date}', '{pump_operator_name}', '{settlement_pumps}',
                    '{total_sale_amount}', '{total_cash}', '{total_cards}', '{total_credit_sales}',
                    '{total_short}', '{total_loans}', '{total_cheques}', '{cash_deposit}', '{total_expenses}',
                    '{total_excess}', '{loan_payments}', '{owners_drawings}', '{mechanical_meter_difference}',
                    '{business_name}', '{location_name}'
                ],
            ],
            'pd_edit_settlements' => [
                'name' => 'Edit PD Settlement',
                'extra_tags' => ['{settlement_no}', '{editted_date}', '{original_details}', '{editted_details}', '{user_editted}'],
            ],
            'pd_day_end_settlement' => [
                'name' => 'PD Day End Settlement',
                'extra_tags' => [
                    '{date}', '{total_sale}', '{total_pos_amount_today}', '{total_pos_cash_amount_today}',
                    '{total_pos_cash_sales_today}', '{total_pos_sales}', '{total_pos_sales_today}', '{pumpers_worked}',
                    '{pumps}', '{total_cash}', '{total_cards}', '{total_credit_sales}', '{total_short}', '{total_loans}',
                    '{total_cheques}', '{cash_deposit}', '{total_expenses}', '{total_excess}', '{loan_payments}',
                    '{owners_drawings}', '{tank_product_qty_difference}', '{fuel_category_products}', '{product_sold_qty}',
                    '{bulk_sale_qty}'
                ],
            ],
            'pd_stock_and_dip_details' => [
                'name' => 'PD Stock and Dip Details',
                'extra_tags' => ['{date_entered}', '{time_entered}', '{dip_details}', '{opening_stock}', '{received_stock}', '{sold_qty}', '{testing_qty}'],
            ],
            'pd_load_received' => [
                'name' => 'PD Load Received',
                'extra_tags' => ['{date}', '{load_details}'],
            ],
            'pd_daily_collection' => [
                'name' => 'PD Daily Collection',
                'extra_tags' => ['{date}', '{time}', '{pump_operator}', '{amount}', '{pumper_cummulative_amount}', '{total_amount}'],
            ],
            'pd_pumper_dashboard_cash_deposit' => [
                'name' => 'PD Pumper Dashboard Cash Deposit',
                'extra_tags' => ['{date}', '{time}', '{pump_operator}', '{amount}'],
            ],
            'pd_pumper_dashboard_credit_sales' => [
                'name' => 'PD Pumper Dashboard Credit Sales',
                'extra_tags' => ['{date}', '{time}', '{pump_operator}', '{customer}', '{amount}', '{order_no}', '{cumulative_amount}', '{customer_reference}'],
            ],
            'pd_pumper_dashboard_credit_sales_customer' => [
                'name' => 'PD Pumper Dashboard Credit Sales Customer',
                'extra_tags' => ['{date}', '{time}', '{pump_operator}', '{customer}', '{amount}', '{order_no}', '{cumulative_amount}', '{customer_reference}'],
            ],
        ];
    }
}
