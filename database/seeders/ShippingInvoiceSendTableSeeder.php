<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ShippingInvoiceSendTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('shipping_invoice_send')->delete();
        
        
        
    }
}