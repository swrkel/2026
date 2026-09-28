<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DefaultNotificationTemplatesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('default_notification_templates')->delete();
        
        \DB::table('default_notification_templates')->insert(array (
            0 => 
            array (
                'id' => 18,
                'template_for' => 'new_sale',
                'email_body' => '<p>Dear {contact_name},</p>

<p>Your invoice number is {invoice_number}<br />
Total amount: {total_amount}<br />
Paid amount: {received_amount}</p>

<p>Thank you for shopping with us!!</p>

<p>{business_logo}</p>

<p>&nbsp;</p>',
                'sms_body' => 'Dear {contact_name},

Your new Credit purchase. Details below;

Date : {transaction_date}
Bill No: {invoice_number},
Total Bill:  {total_amount}
Paid: {paid_amount}
Balance: {due_amount}

Total Balance: {cumulative_due_amount}

Thank you.',
                'subject' => 'Thank you from {business_name}.',
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2024-01-07 13:54:19',
            ),
            1 => 
            array (
                'id' => 19,
                'template_for' => 'payment_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received a payment of {received_amount}</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have received a payment of {received_amount}.
Balance Due: {due_amount}
{business_name}

If you have any clarifications, contact us.',
                'subject' => 'Payment Received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            2 => 
            array (
                'id' => 20,
                'template_for' => 'payment_reminder',
                'email_body' => '<p>Dear {contact_name},</p>

<p>This is to remind you that you have pending payment of {due_amount}. Kindly pay it as soon as possible.</p>

<p>{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, You have pending payment of {due_amount}. Kindly pay it as soon as possible. {business_name};',
                'subject' => 'Payment Reminder, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2023-11-14 13:45:06',
            ),
            3 => 
            array (
                'id' => 21,
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
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2023-11-08 16:15:51',
            ),
            4 => 
            array (
                'id' => 22,
                'template_for' => 'new_order',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible!!</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'Dear {contact_name}, We have a new order with reference number {order_ref_number}. Kindly process the products as soon as possible. {business_name}',
                'subject' => 'New Order, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2024-01-07 13:54:41',
            ),
            5 => 
            array (
                'id' => 23,
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
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2023-06-08 21:45:53',
            ),
            6 => 
            array (
                'id' => 24,
                'template_for' => 'items_received',
                'email_body' => '<p>Dear {contact_name},</p>

<p>We have received all items from invoice reference number {order_ref_number}. Thank you for processing it.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'We have received all items from invoice reference number {order_ref_number}. Thank you for processing it. {business_name}',
                'subject' => 'Items received, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2023-06-08 21:45:53',
            ),
            7 => 
            array (
                'id' => 25,
                'template_for' => 'items_pending',
                'email_body' => '<p>Dear {contact_name},<br />
This is to remind you that we have not yet received some items from invoice reference number {order_ref_number}. Please process it as soon as possible.</p>

<p>{business_name}<br />
{business_logo}</p>',
                'sms_body' => 'This is to remind you that we have not yet received some items from invoice reference number {order_ref_number} . Please process it as soon as possible.{business_name}',
                'subject' => 'Items Pending, from {business_name}',
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2023-06-08 21:45:53',
            ),
            8 => 
            array (
                'id' => 26,
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
                'created_at' => '2022-05-03 09:36:06',
                'updated_at' => '2023-06-08 21:45:53',
            ),
            9 => 
            array (
                'id' => 36,
                'template_for' => 'send_ledger',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-06-08 03:16:48',
                'updated_at' => '2023-06-08 03:16:48',
            ),
            10 => 
            array (
                'id' => 37,
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
                'created_at' => '2023-06-08 03:16:48',
                'updated_at' => '2023-06-08 09:39:01',
            ),
            11 => 
            array (
                'id' => 38,
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
                'created_at' => '2023-06-08 03:16:48',
                'updated_at' => '2023-06-08 09:39:01',
            ),
            12 => 
            array (
                'id' => 39,
                'template_for' => 'deposit',
                'email_body' => NULL,
                'sms_body' => 'The following deposti have been made Amount: {amount}, Account: {account}, Date: {date}, Staff: {staff}.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 22:10:30',
                'updated_at' => '2023-06-08 22:10:30',
            ),
            13 => 
            array (
                'id' => 40,
                'template_for' => 'transfer',
                'email_body' => NULL,
                'sms_body' => 'The following transfer have been made Amount: {amount}, Account: {account}, Date: {date}, Staff: {staff}.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 22:10:30',
                'updated_at' => '2023-06-08 22:10:30',
            ),
            14 => 
            array (
                'id' => 41,
                'template_for' => 'expense_created',
                'email_body' => NULL,
                'sms_body' => 'The following expense was created: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 22:10:30',
                'updated_at' => '2023-06-08 22:10:30',
            ),
            15 => 
            array (
                'id' => 42,
                'template_for' => 'expense_deleted',
                'email_body' => NULL,
                'sms_body' => 'Epense Deleted: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 22:10:30',
                'updated_at' => '2023-06-08 22:10:30',
            ),
            16 => 
            array (
                'id' => 43,
                'template_for' => 'expense_changed',
                'email_body' => NULL,
                'sms_body' => 'The following modified was created: Ref: {ref}, Amount: {amount}, Account: {account},Added by {staff}',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-06-08 22:10:30',
                'updated_at' => '2023-06-08 22:10:30',
            ),
            17 => 
            array (
                'id' => 44,
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
                'created_at' => '2023-06-09 08:14:55',
                'updated_at' => '2023-06-10 10:45:50',
            ),
            18 => 
            array (
                'id' => 45,
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
                'created_at' => '2023-07-31 11:30:57',
                'updated_at' => '2023-07-31 11:30:57',
            ),
            19 => 
            array (
                'id' => 55,
                'template_for' => 'payment_deleted',
                'email_body' => NULL,
                'sms_body' => 'Dear {contact_name}, your payment;  Ref {payment_ref_number}, Amount: {received_amount} has been deleted.',
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 1,
                'created_at' => '2023-11-15 12:01:02',
                'updated_at' => '2023-11-15 12:01:02',
            ),
            20 => 
            array (
                'id' => 56,
                'template_for' => 'payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 06:36:34',
                'updated_at' => '2023-12-09 06:36:34',
            ),
            21 => 
            array (
                'id' => 57,
                'template_for' => 'sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 06:36:34',
                'updated_at' => '2023-12-09 06:36:34',
            ),
            22 => 
            array (
                'id' => 58,
                'template_for' => 'purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-09 06:36:34',
                'updated_at' => '2023-12-09 06:36:34',
            ),
            23 => 
            array (
                'id' => 59,
                'template_for' => 'customer_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            24 => 
            array (
                'id' => 60,
                'template_for' => 'customer_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            25 => 
            array (
                'id' => 61,
                'template_for' => 'customer_sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            26 => 
            array (
                'id' => 62,
                'template_for' => 'supplier_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            27 => 
            array (
                'id' => 63,
                'template_for' => 'supplier_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            28 => 
            array (
                'id' => 64,
                'template_for' => 'supplier_expense_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            29 => 
            array (
                'id' => 65,
                'template_for' => 'supplier_purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            30 => 
            array (
                'id' => 66,
                'template_for' => 'general_payment_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            31 => 
            array (
                'id' => 67,
                'template_for' => 'general_payment_editted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            32 => 
            array (
                'id' => 68,
                'template_for' => 'general_expense_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            33 => 
            array (
                'id' => 69,
                'template_for' => 'general_purchase_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            34 => 
            array (
                'id' => 70,
                'template_for' => 'general_sale_deleted',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
            35 => 
            array (
                'id' => 71,
                'template_for' => 'customer_loan_given',
                'email_body' => NULL,
                'sms_body' => NULL,
                'subject' => NULL,
                'auto_send' => 0,
                'auto_send_sms' => 0,
                'created_at' => '2023-12-13 06:57:47',
                'updated_at' => '2023-12-13 06:57:47',
            ),
        ));
        
        
    }
}