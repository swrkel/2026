<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConfigTableSeeder extends Seeder
{

  /**
   * Auto generated seed file
   *
   * @return void
   */
  public function run()
  {


    DB::table('config')->delete();

    DB::table('config')->insert(array(
      0 =>
      array(
        'id' => 1,
        'config_key' => 'site_name',
        'config_value' => 'EzyPetRoInternational',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      1 =>
      array(
        'id' => 2,
        'config_key' => 'currency',
        'config_value' => 'LKR',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      2 =>
      array(
        'id' => 3,
        'config_key' => 'timezone',
        'config_value' => 'Asia/Colombo',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      3 =>
      array(
        'id' => 4,
        'config_key' => 'paypal_mode',
        'config_value' => 'sandbox',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      4 =>
      array(
        'id' => 5,
        'config_key' => 'paypal_client_id',
        'config_value' => 'YOUR_PAYPAL_CLIENT_ID',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      5 =>
      array(
        'id' => 6,
        'config_key' => 'paypal_secret',
        'config_value' => 'YOUR_PAYPAL_SECRET',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      6 =>
      array(
        'id' => 7,
        'config_key' => 'razorpay_key',
        'config_value' => 'YOUR_RAZORPAY_KEY',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      7 =>
      array(
        'id' => 8,
        'config_key' => 'razorpay_secret',
        'config_value' => 'YOUR_RAZORPAY_SECRET',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      8 =>
      array(
        'id' => 9,
        'config_key' => 'term',
        'config_value' => 'monthly',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      9 =>
      array(
        'id' => 10,
        'config_key' => 'stripe_publishable_key',
        'config_value' => 'YOUR_STRIPE_PUB_KEY',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      10 =>
      array(
        'id' => 11,
        'config_key' => 'stripe_secret',
        'config_value' => 'YOUR_STRIPE_SECRET',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      11 =>
      array(
        'id' => 12,
        'config_key' => 'app_theme',
        'config_value' => 'purple',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      12 =>
      array(
        'id' => 13,
        'config_key' => 'primary_image',
        'config_value' => '/frontend/assets/elements/IMG-1720891987.png',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      13 =>
      array(
        'id' => 14,
        'config_key' => 'secondary_image',
        'config_value' => '/frontend/assets/IMG-1726747744.png',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      14 =>
      array(
        'id' => 15,
        'config_key' => 'tax_type',
        'config_value' => 'exclusive',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      15 =>
      array(
        'id' => 16,
        'config_key' => 'invoice_prefix',
        'config_value' => 'INV-',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      16 =>
      array(
        'id' => 17,
        'config_key' => 'invoice_name',
        'config_value' => 'SYZYGY vCards',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      17 =>
      array(
        'id' => 18,
        'config_key' => 'invoice_email',
        'config_value' => 'support@syzygyvcard.com',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      18 =>
      array(
        'id' => 19,
        'config_key' => 'invoice_phone',
        'config_value' => '+94774055434',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      19 =>
      array(
        'id' => 20,
        'config_key' => 'invoice_address',
        'config_value' => 'Sudarshanarama Road',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      20 =>
      array(
        'id' => 21,
        'config_key' => 'invoice_city',
        'config_value' => 'Malabe',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      21 =>
      array(
        'id' => 22,
        'config_key' => 'invoice_state',
        'config_value' => 'Western',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      22 =>
      array(
        'id' => 23,
        'config_key' => 'invoice_zipcode',
        'config_value' => '10161',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      23 =>
      array(
        'id' => 24,
        'config_key' => 'invoice_country',
        'config_value' => 'Sri Lanka',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      24 =>
      array(
        'id' => 25,
        'config_key' => 'tax_name',
        'config_value' => 'VAT',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      25 =>
      array(
        'id' => 26,
        'config_key' => 'tax_value',
        'config_value' => '0',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      26 =>
      array(
        'id' => 27,
        'config_key' => 'tax_number',
        'config_value' => '0',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      27 =>
      array(
        'id' => 28,
        'config_key' => 'email_heading',
        'config_value' => 'Thanks for using SYZYGY V Cards. This is an invoice for your recent purchase.',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      28 =>
      array(
        'id' => 29,
        'config_key' => 'email_footer',
        'config_value' => 'If you’re having trouble with the button above, please login into your web browser.',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      29 =>
      array(
        'id' => 30,
        'config_key' => 'invoice_footer',
        'config_value' => 'Thank you very much for doing business with us. We look forward to working with you again!',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      30 =>
      array(
        'id' => 31,
        'config_key' => 'share_content',
        'config_value' => 'Glad to share my Digital Business Card, { business_url } 

Powered by: SYZYGY Technologies, Malabe, Sri Lanka
https://syzygyvcard.com/',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      31 =>
      array(
        'id' => 32,
        'config_key' => 'bank_transfer',
        'config_value' => 'Account Name: SYZYGY Consultancy Pvt Ltd
Account Number: 0039 1000 6966
Bank: Sampath Bank
Branch: Malabe
Swift Code: BSAMLKLXXXX',
        'created_at' => '2021-10-09 16:02:44',
        'updated_at' => '2021-10-09 16:02:44',
      ),
      32 =>
      array(
        'id' => 33,
        'config_key' => 'payhere_merchant_secret',
        'config_value' => 'b2bd332758c7c54ef056534f65f3fd77',
        'created_at' => '2021-10-15 15:11:54',
        'updated_at' => '2021-10-15 15:11:54',
      ),
      33 =>
      array(
        'id' => 34,
        'config_key' => 'payhere_merchant_id',
        'config_value' => '211559',
        'created_at' => '2021-10-15 15:11:54',
        'updated_at' => '2021-10-15 15:11:54',
      ),
      34 =>
      array(
        'id' => 35,
        'config_key' => 'register_image',
        'config_value' => '/frontend/assets/IMG-1685666264.png',
        'created_at' => '2021-10-19 12:07:33',
        'updated_at' => '2021-10-19 12:07:33',
      ),
    ));
  }
}
