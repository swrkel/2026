<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class NotificationTemplatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('notification_templates')->delete();
        
        \DB::table('notification_templates')->insert(array (
            0 => 
            array (
                'id' => 1,
                'business_id' => 1,
                'template_for' => 'new_sale',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your invoice number is {invoice_number}<br />
Total amount: {total_amount}<br />
Paid amount: {paid_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'business_id' => 1,
                'template_for' => 'payment_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received a payment of {paid_amount}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have received a payment of {paid_amount}. {business_name}',
                'subject' => 'Payment Received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'business_id' => 1,
                'template_for' => 'payment_reminder',
                'email_body' => '<p>Dear {contact_name},</p>

<p>This is to remind you that you have pending payment of {due_amount}. Kindly pay it as soon as possible.</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, You have pending payment of {due_amount}. Kindly pay it as soon as possible. {business_name}',
                'subject' => 'Payment Reminder, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'business_id' => 1,
                'template_for' => 'new_booking',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your booking is confirmed</p>

<p>Date: {start_time} to {end_time}</p>

<p>Table: {table}</p>

<p>Location: {location}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, Your booking is confirmed. Date: {start_time} to {end_time}, Table: {table}, Location: {location}',
                'subject' => 'Booking Confirmed - {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            4 => 
            array (
                'id' => 5,
                'business_id' => 1,
                'template_for' => 'new_order',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have a new order with reference number {invoice_number}. Kindly process the products as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have a new order with reference number {invoice_number}. Kindly process the products as soon as possible. {business_name}',
                'subject' => 'New Order, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            5 => 
            array (
                'id' => 6,
                'business_id' => 1,
                'template_for' => 'payment_paid',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have paid amount {paid_amount} again invoice number {invoice_number}.<br />
Kindly note it down.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have paid amount {paid_amount} again invoice number {invoice_number}.
Kindly note it down. {business_name}',
                'subject' => 'Payment Paid, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            6 => 
            array (
                'id' => 7,
                'business_id' => 1,
                'template_for' => 'items_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received all items from invoice reference number {invoice_number}. Thank you for processing it.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have received all items from invoice reference number {invoice_number}. Thank you for processing it. {business_name}',
                'subject' => 'Items received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            7 => 
            array (
                'id' => 8,
                'business_id' => 1,
                'template_for' => 'items_pending',
                'email_body' => '<p>Dear {contact_name},<br />
This is to remind you that we have not yet received some items from invoice reference number {invoice_number}. Please process it as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'This is to remind you that we have not yet received some items from invoice reference number {invoice_number} . Please process it as soon as possible.{business_name}',
                'subject' => 'Items Pending, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2019-12-30 12:22:03',
                'updated_at' => '2019-12-30 12:22:03',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            8 => 
            array (
                'id' => 9,
                'business_id' => 3,
                'template_for' => 'transaction_changed',
                'email_body' => NULL,
                'sms_body' => 'The following {transaction_type} transaction has been deleted. 
{account_name}
{amount}
{transaction_date}
{invoice_no}
{staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 03:16:48',
                'updated_at' => '2023-06-08 03:16:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            9 => 
            array (
                'id' => 10,
                'business_id' => 3,
                'template_for' => 'transaction_deleted',
                'email_body' => NULL,
                'sms_body' => 'The following {transaction_type} transaction has been deleted. 
{account_name}
{amount}
{transaction_date}
{invoice_no}
{staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 03:16:48',
                'updated_at' => '2023-06-08 03:16:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            10 => 
            array (
                'id' => 11,
                'business_id' => 1,
                'template_for' => 'new_quotation',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your quotation number is {invoice_number}<br />
Total amount: {total_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 04:06:06',
                'updated_at' => '2023-06-08 16:15:53',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            11 => 
            array (
                'id' => 12,
                'business_id' => 1,
                'template_for' => 'send_ledger',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-06-07 21:46:48',
                'updated_at' => '2023-06-07 21:46:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            12 => 
            array (
                'id' => 13,
                'business_id' => 1,
                'template_for' => 'transaction_deleted',
                'email_body' => NULL,
                'sms_body' => 'The following {transaction_type} transaction has been deleted. 
Account: {account_name}
Amount: {amount}
Transaction Date: {transaction_date}
Ref: {invoice_no}
Change By: {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-07 21:46:48',
                'updated_at' => '2023-06-08 04:09:01',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            13 => 
            array (
                'id' => 14,
                'business_id' => 1,
                'template_for' => 'transaction_changed',
                'email_body' => NULL,
                'sms_body' => 'The following {transaction_type} transaction has been deleted. 
Account: {account_name}
Amount: {amount}
Transaction Date: {transaction_date}
Ref: {invoice_no}
Change By: {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-07 21:46:48',
                'updated_at' => '2023-06-08 04:09:01',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            14 => 
            array (
                'id' => 15,
                'business_id' => 1,
                'template_for' => 'deposit',
                'email_body' => NULL,
                'sms_body' => 'The following deposti have been made Amount: {amount}, Account: {account}, Date: {date}, Staff: {staff}.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            15 => 
            array (
                'id' => 16,
                'business_id' => 1,
                'template_for' => 'transfer',
                'email_body' => NULL,
                'sms_body' => 'The following transfer have been made Amount: {amount}, Account: {account}, Date: {date}, Staff: {staff}.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            16 => 
            array (
                'id' => 17,
                'business_id' => 1,
                'template_for' => 'expense_created',
                'email_body' => NULL,
                'sms_body' => 'The following expense was created: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            17 => 
            array (
                'id' => 18,
                'business_id' => 1,
                'template_for' => 'expense_deleted',
                'email_body' => NULL,
                'sms_body' => 'Epense Deleted: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            18 => 
            array (
                'id' => 19,
                'business_id' => 1,
                'template_for' => 'expense_changed',
                'email_body' => NULL,
                'sms_body' => 'The following modified was created: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            19 => 
            array (
                'id' => 20,
                'business_id' => 1,
                'template_for' => 'credit_sale',
                'email_body' => NULL,
                'sms_body' => '{business_name}, 
{contact_name},
{invoice_number},
{total_amount},
{paid_amount},
{due_amount},
{cumulative_due_amount}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-09 02:44:55',
                'updated_at' => '2023-06-10 05:15:50',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            20 => 
            array (
                'id' => 21,
                'business_id' => 1,
                'template_for' => 'cash_deposit',
                'email_body' => NULL,
                'sms_body' => 'New cash deposit:
Amount : {amount}
Bank: {bank}
Account no: {account}
Time Deposited: {time}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-07-31 06:00:57',
                'updated_at' => '2023-07-31 06:00:57',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            21 => 
            array (
                'id' => 22,
                'business_id' => 1,
                'template_for' => 'payment_deleted',
                'email_body' => NULL,
                'sms_body' => 'Dear {contact_name}, your payment;  Ref {payment_ref_number}, Amount: {received_amount} has been deleted.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-11-15 06:31:02',
                'updated_at' => '2023-11-15 06:31:02',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            22 => 
            array (
                'id' => 23,
                'business_id' => 1,
                'template_for' => 'payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 01:06:34',
                'updated_at' => '2023-12-09 01:06:34',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            23 => 
            array (
                'id' => 24,
                'business_id' => 1,
                'template_for' => 'sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 01:06:34',
                'updated_at' => '2023-12-09 01:06:34',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            24 => 
            array (
                'id' => 25,
                'business_id' => 1,
                'template_for' => 'purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 01:06:34',
                'updated_at' => '2023-12-09 01:06:34',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            25 => 
            array (
                'id' => 26,
                'business_id' => 1,
                'template_for' => 'customer_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            26 => 
            array (
                'id' => 27,
                'business_id' => 1,
                'template_for' => 'customer_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            27 => 
            array (
                'id' => 28,
                'business_id' => 1,
                'template_for' => 'customer_sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            28 => 
            array (
                'id' => 29,
                'business_id' => 1,
                'template_for' => 'supplier_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            29 => 
            array (
                'id' => 30,
                'business_id' => 1,
                'template_for' => 'supplier_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            30 => 
            array (
                'id' => 31,
                'business_id' => 1,
                'template_for' => 'supplier_expense_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            31 => 
            array (
                'id' => 32,
                'business_id' => 1,
                'template_for' => 'supplier_purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            32 => 
            array (
                'id' => 33,
                'business_id' => 1,
                'template_for' => 'general_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            33 => 
            array (
                'id' => 34,
                'business_id' => 1,
                'template_for' => 'general_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            34 => 
            array (
                'id' => 35,
                'business_id' => 1,
                'template_for' => 'general_expense_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            35 => 
            array (
                'id' => 36,
                'business_id' => 1,
                'template_for' => 'general_purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            36 => 
            array (
                'id' => 37,
                'business_id' => 1,
                'template_for' => 'general_sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            37 => 
            array (
                'id' => 38,
                'business_id' => 1,
                'template_for' => 'customer_loan_given',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            38 => 
            array (
                'id' => 39,
                'business_id' => 2,
                'template_for' => 'new_sale',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your invoice number is {invoice_number}<br />
Total amount: {total_amount}<br />
Paid amount: {received_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            39 => 
            array (
                'id' => 40,
                'business_id' => 2,
                'template_for' => 'payment_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received a payment of {received_amount}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have received a payment of {received_amount}. {business_name}',
                'subject' => 'Payment Received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            40 => 
            array (
                'id' => 41,
                'business_id' => 2,
                'template_for' => 'payment_reminder',
                'email_body' => '<p>Dear {contact_name},</p>

<p>This is to remind you that you have pending payment of {due_amount}. Kindly pay it as soon as possible.</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, You have pending payment of {due_amount}. Kindly pay it as soon as possible. {business_name}',
                'subject' => 'Payment Reminder, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            41 => 
            array (
                'id' => 42,
                'business_id' => 2,
                'template_for' => 'new_booking',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your booking is confirmed</p>

<p>Date: {start_time} to {end_time}</p>

<p>Table: {table}</p>

<p>Location: {location}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, Your booking is confirmed. Date: {start_time} to {end_time}, Table: {table}, Location: {location}',
                'subject' => 'Booking Confirmed - {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            42 => 
            array (
                'id' => 43,
                'business_id' => 2,
                'template_for' => 'new_order',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible. {business_name}',
                'subject' => 'New Order, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            43 => 
            array (
                'id' => 44,
                'business_id' => 2,
                'template_for' => 'payment_paid',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have paid amount {paid_amount} again invoice number {order_ref_number}.<br />
Kindly note it down.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have paid amount {paid_amount} again invoice number {order_ref_number}.
Kindly note it down. {business_name}',
                'subject' => 'Payment Paid, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            44 => 
            array (
                'id' => 45,
                'business_id' => 2,
                'template_for' => 'items_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received all items from invoice reference number {order_ref_number}. Thank you for processing it.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have received all items from invoice reference number {order_ref_number}. Thank you for processing it. {business_name}',
                'subject' => 'Items received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            45 => 
            array (
                'id' => 46,
                'business_id' => 2,
                'template_for' => 'items_pending',
                'email_body' => '<p>Dear {contact_name},<br />
This is to remind you that we have not yet received some items from invoice reference number {order_ref_number}. Please process it as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'This is to remind you that we have not yet received some items from invoice reference number {order_ref_number} . Please process it as soon as possible.{business_name}',
                'subject' => 'Items Pending, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            46 => 
            array (
                'id' => 47,
                'business_id' => 2,
                'template_for' => 'new_quotation',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your quotation number is {invoice_number}<br />
Total amount: {total_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-28 23:42:56',
                'updated_at' => '2024-01-28 23:42:56',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            47 => 
            array (
                'id' => 48,
                'business_id' => 3,
                'template_for' => 'new_sale',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your invoice number is {invoice_number}<br />
Total amount: {total_amount}<br />
Paid amount: {received_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            48 => 
            array (
                'id' => 49,
                'business_id' => 3,
                'template_for' => 'payment_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received a payment of {received_amount}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have received a payment of {received_amount}. {business_name}',
                'subject' => 'Payment Received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            49 => 
            array (
                'id' => 50,
                'business_id' => 3,
                'template_for' => 'payment_reminder',
                'email_body' => '<p>Dear {contact_name},</p>

<p>This is to remind you that you have pending payment of {due_amount}. Kindly pay it as soon as possible.</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, You have pending payment of {due_amount}. Kindly pay it as soon as possible. {business_name}',
                'subject' => 'Payment Reminder, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            50 => 
            array (
                'id' => 51,
                'business_id' => 3,
                'template_for' => 'new_booking',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your booking is confirmed</p>

<p>Date: {start_time} to {end_time}</p>

<p>Table: {table}</p>

<p>Location: {location}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, Your booking is confirmed. Date: {start_time} to {end_time}, Table: {table}, Location: {location}',
                'subject' => 'Booking Confirmed - {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            51 => 
            array (
                'id' => 52,
                'business_id' => 3,
                'template_for' => 'new_order',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible. {business_name}',
                'subject' => 'New Order, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            52 => 
            array (
                'id' => 53,
                'business_id' => 3,
                'template_for' => 'payment_paid',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have paid amount {paid_amount} again invoice number {order_ref_number}.<br />
Kindly note it down.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have paid amount {paid_amount} again invoice number {order_ref_number}.
Kindly note it down. {business_name}',
                'subject' => 'Payment Paid, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            53 => 
            array (
                'id' => 54,
                'business_id' => 3,
                'template_for' => 'items_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received all items from invoice reference number {order_ref_number}. Thank you for processing it.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have received all items from invoice reference number {order_ref_number}. Thank you for processing it. {business_name}',
                'subject' => 'Items received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            54 => 
            array (
                'id' => 55,
                'business_id' => 3,
                'template_for' => 'items_pending',
                'email_body' => '<p>Dear {contact_name},<br />
This is to remind you that we have not yet received some items from invoice reference number {order_ref_number}. Please process it as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'This is to remind you that we have not yet received some items from invoice reference number {order_ref_number} . Please process it as soon as possible.{business_name}',
                'subject' => 'Items Pending, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            55 => 
            array (
                'id' => 56,
                'business_id' => 3,
                'template_for' => 'new_quotation',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your quotation number is {invoice_number}<br />
Total amount: {total_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-01-30 15:01:48',
                'updated_at' => '2024-01-30 15:01:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            56 => 
            array (
                'id' => 57,
                'business_id' => 4,
                'template_for' => 'new_sale',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your invoice number is {invoice_number}<br />
Total amount: {total_amount}<br />
Paid amount: {received_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            57 => 
            array (
                'id' => 58,
                'business_id' => 4,
                'template_for' => 'payment_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received a payment of {received_amount}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have received a payment of {received_amount}. {business_name}',
                'subject' => 'Payment Received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            58 => 
            array (
                'id' => 59,
                'business_id' => 4,
                'template_for' => 'payment_reminder',
                'email_body' => '<p>Dear {contact_name},</p>

<p>This is to remind you that you have pending payment of {due_amount}. Kindly pay it as soon as possible.</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, You have pending payment of {due_amount}. Kindly pay it as soon as possible. {business_name}',
                'subject' => 'Payment Reminder, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            59 => 
            array (
                'id' => 60,
                'business_id' => 4,
                'template_for' => 'new_booking',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your booking is confirmed</p>

<p>Date: {start_time} to {end_time}</p>

<p>Table: {table}</p>

<p>Location: {location}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, Your booking is confirmed. Date: {start_time} to {end_time}, Table: {table}, Location: {location}',
                'subject' => 'Booking Confirmed - {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            60 => 
            array (
                'id' => 61,
                'business_id' => 4,
                'template_for' => 'new_order',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible. {business_name}',
                'subject' => 'New Order, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            61 => 
            array (
                'id' => 62,
                'business_id' => 4,
                'template_for' => 'payment_paid',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have paid amount {paid_amount} again invoice number {order_ref_number}.<br />
Kindly note it down.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have paid amount {paid_amount} again invoice number {order_ref_number}.
Kindly note it down. {business_name}',
                'subject' => 'Payment Paid, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            62 => 
            array (
                'id' => 63,
                'business_id' => 4,
                'template_for' => 'items_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received all items from invoice reference number {order_ref_number}. Thank you for processing it.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have received all items from invoice reference number {order_ref_number}. Thank you for processing it. {business_name}',
                'subject' => 'Items received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            63 => 
            array (
                'id' => 64,
                'business_id' => 4,
                'template_for' => 'items_pending',
                'email_body' => '<p>Dear {contact_name},<br />
This is to remind you that we have not yet received some items from invoice reference number {order_ref_number}. Please process it as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'This is to remind you that we have not yet received some items from invoice reference number {order_ref_number} . Please process it as soon as possible.{business_name}',
                'subject' => 'Items Pending, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            64 => 
            array (
                'id' => 65,
                'business_id' => 4,
                'template_for' => 'new_quotation',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your quotation number is {invoice_number}<br />
Total amount: {total_amount}</p>

<p>Thank you for shopping with us.</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name}, Thank you for shopping with us. {business_name}',
                'subject' => 'Thank you from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2024-02-26 15:20:51',
                'updated_at' => '2024-02-26 15:20:51',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            65 => 
            array (
                'id' => 66,
                'business_id' => 4,
                'template_for' => 'send_ledger',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-06-07 21:46:48',
                'updated_at' => '2023-06-07 21:46:48',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            66 => 
            array (
                'id' => 67,
                'business_id' => 4,
                'template_for' => 'transaction_deleted',
                'email_body' => NULL,
                'sms_body' => 'The following {transaction_type} transaction has been deleted. 
Account: {account_name}
Amount: {amount}
Transaction Date: {transaction_date}
Ref: {invoice_no}
Change By: {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-07 21:46:48',
                'updated_at' => '2023-06-08 04:09:01',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            67 => 
            array (
                'id' => 68,
                'business_id' => 4,
                'template_for' => 'transaction_changed',
                'email_body' => NULL,
                'sms_body' => 'The following {transaction_type} transaction has been deleted. 
Account: {account_name}
Amount: {amount}
Transaction Date: {transaction_date}
Ref: {invoice_no}
Change By: {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-07 21:46:48',
                'updated_at' => '2023-06-08 04:09:01',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            68 => 
            array (
                'id' => 69,
                'business_id' => 4,
                'template_for' => 'deposit',
                'email_body' => NULL,
                'sms_body' => 'The following deposti have been made Amount: {amount}, Account: {account}, Date: {date}, Staff: {staff}.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            69 => 
            array (
                'id' => 70,
                'business_id' => 4,
                'template_for' => 'transfer',
                'email_body' => NULL,
                'sms_body' => 'The following transfer have been made Amount: {amount}, Account: {account}, Date: {date}, Staff: {staff}.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            70 => 
            array (
                'id' => 71,
                'business_id' => 4,
                'template_for' => 'expense_created',
                'email_body' => NULL,
                'sms_body' => 'The following expense was created: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            71 => 
            array (
                'id' => 72,
                'business_id' => 4,
                'template_for' => 'expense_deleted',
                'email_body' => NULL,
                'sms_body' => 'Epense Deleted: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            72 => 
            array (
                'id' => 73,
                'business_id' => 4,
                'template_for' => 'expense_changed',
                'email_body' => NULL,
                'sms_body' => 'The following modified was created: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 16:40:30',
                'updated_at' => '2023-06-08 16:40:30',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            73 => 
            array (
                'id' => 74,
                'business_id' => 4,
                'template_for' => 'credit_sale',
                'email_body' => NULL,
                'sms_body' => '{business_name}, 
{contact_name},
{invoice_number},
{total_amount},
{paid_amount},
{due_amount},
{cumulative_due_amount}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-09 02:44:55',
                'updated_at' => '2023-06-10 05:15:50',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            74 => 
            array (
                'id' => 75,
                'business_id' => 4,
                'template_for' => 'cash_deposit',
                'email_body' => NULL,
                'sms_body' => 'New cash deposit:
Amount : {amount}
Bank: {bank}
Account no: {account}
Time Deposited: {time}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-07-31 06:00:57',
                'updated_at' => '2023-07-31 06:00:57',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            75 => 
            array (
                'id' => 76,
                'business_id' => 4,
                'template_for' => 'payment_deleted',
                'email_body' => NULL,
                'sms_body' => 'Dear {contact_name}, your payment;  Ref {payment_ref_number}, Amount: {received_amount} has been deleted.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-11-15 06:31:02',
                'updated_at' => '2023-11-15 06:31:02',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            76 => 
            array (
                'id' => 77,
                'business_id' => 4,
                'template_for' => 'payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 01:06:34',
                'updated_at' => '2023-12-09 01:06:34',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            77 => 
            array (
                'id' => 78,
                'business_id' => 4,
                'template_for' => 'sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 01:06:34',
                'updated_at' => '2023-12-09 01:06:34',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            78 => 
            array (
                'id' => 79,
                'business_id' => 4,
                'template_for' => 'purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 01:06:34',
                'updated_at' => '2023-12-09 01:06:34',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            79 => 
            array (
                'id' => 80,
                'business_id' => 4,
                'template_for' => 'customer_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            80 => 
            array (
                'id' => 81,
                'business_id' => 4,
                'template_for' => 'customer_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            81 => 
            array (
                'id' => 82,
                'business_id' => 4,
                'template_for' => 'customer_sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            82 => 
            array (
                'id' => 83,
                'business_id' => 4,
                'template_for' => 'supplier_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            83 => 
            array (
                'id' => 84,
                'business_id' => 4,
                'template_for' => 'supplier_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            84 => 
            array (
                'id' => 85,
                'business_id' => 4,
                'template_for' => 'supplier_expense_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            85 => 
            array (
                'id' => 86,
                'business_id' => 4,
                'template_for' => 'supplier_purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            86 => 
            array (
                'id' => 87,
                'business_id' => 4,
                'template_for' => 'general_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            87 => 
            array (
                'id' => 88,
                'business_id' => 4,
                'template_for' => 'general_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            88 => 
            array (
                'id' => 89,
                'business_id' => 4,
                'template_for' => 'general_expense_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            89 => 
            array (
                'id' => 90,
                'business_id' => 4,
                'template_for' => 'general_purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            90 => 
            array (
                'id' => 91,
                'business_id' => 4,
                'template_for' => 'general_sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
            91 => 
            array (
                'id' => 92,
                'business_id' => 4,
                'template_for' => 'customer_loan_given',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 01:27:47',
                'updated_at' => '2023-12-13 01:27:47',
                'phone_nos' => NULL,
                'notification_category' => NULL,
            ),
        ));
        
        
    }
}