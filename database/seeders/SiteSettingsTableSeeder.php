<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SiteSettingsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('site_settings')->delete();
        
        \DB::table('site_settings')->insert(array (
            0 => 
            array (
                'id' => 1,
                'logingLogo_width' => 400,
                'logingLogo_height' => 200,
                'uploadFileFicon' => 'public/img/setting/1641073223.png',
                'uploadFileLBackground' => 'public/img/setting/1641073241.png',
                'uploadFileLLogo' => 'public/img/setting/1641073223.png',
                'login_background_color' => '#7E75B4',
                'login_box_color' => '#34523',
                'topBelt_background_color' => '#9900cc',
                'background_showing_type' => '1',
                'tc_sale_and_pos' => 1,
                'sales_agents_registration' => 0,
                'main_module_color' => '',
                'sub_module_color' => '#00ff00',
                'sub_module_bg_color' => '#444444',
                'ls_side_menu_bg_color' => '#FFFFFF',
                'ls_side_menu_font_color' => '#00004d',
                'register_now_btn_bg' => NULL,
                'customer_register_btn_bg' => NULL,
                'member_register_btn_bg' => NULL,
                'pricing_btn_bg' => NULL,
                'member_register_bg' => NULL,
                'self_register_bg' => NULL,
                'admin_login_bg' => NULL,
                'customer_login_bg' => NULL,
                'member_login_bg' => NULL,
                'employee_login_bg' => NULL,
                'visitor_login_bg' => NULL,
                'show_messages' => '{"lp_title":"1"}',
                'login_page_title' => 'Login Page Title',
                'login_page_footer' => '© All Rights Reserved.| Version 9.9 | SYZYGY Technologies, Malabe, Sri Lanka. | Tel: 077 4055 434 / 071 1616 192',
                'login_page_description' => 'Login Page Description',
                'login_page_general_message' => 'System general message',
                'system_expired_message' => 'System Expired Message',
                'invoice_footer' => 'Invoice Footer Text',
                'tour_toggle' => 0,
                'created_at' => NULL,
                'updated_at' => '2022-01-01 22:40:41',
                'captch_site_key' => '6Le0JCsdAAAAAMz-cD3AL5Xh-SOCUCaXIP8dbZmi',
                'login_vehicle_registration' => 0,
                'landingPage_settings' => '{"about":1,"how_it_works":1,"features":1,"pricing":0,"contact":1,"language":1,"faq":1,"login":1,"signup":0}',
            ),
        ));
        
        
    }
}