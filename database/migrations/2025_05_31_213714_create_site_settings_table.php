<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('logingLogo_width');
            $table->integer('logingLogo_height');
            $table->string('uploadFileFicon');
            $table->string('uploadFileLBackground');
            $table->string('uploadFileLLogo');
            $table->string('login_background_color');
            $table->string('login_box_color');
            $table->string('topBelt_background_color');
            $table->string('background_showing_type');
            $table->boolean('tc_sale_and_pos')->default(false);
            $table->boolean('sales_agents_registration')->default(false);
            $table->string('main_module_color');
            $table->string('sub_module_color');
            $table->string('sub_module_bg_color', 30)->nullable();
            $table->string('ls_side_menu_bg_color', 30)->nullable();
            $table->string('ls_side_menu_font_color', 30)->nullable();
            $table->string('register_now_btn_bg', 50)->nullable();
            $table->string('customer_register_btn_bg', 50)->nullable();
            $table->string('member_register_btn_bg', 50)->nullable();
            $table->string('pricing_btn_bg', 50)->nullable();
            $table->string('member_register_bg', 50)->nullable();
            $table->string('self_register_bg', 50)->nullable();
            $table->string('admin_login_bg', 50)->nullable();
            $table->string('customer_login_bg', 50)->nullable();
            $table->string('member_login_bg', 50)->nullable();
            $table->string('employee_login_bg', 50)->nullable();
            $table->string('visitor_login_bg', 20)->nullable();
            $table->string('show_messages');
            $table->string('login_page_title');
            $table->string('login_page_footer');
            $table->string('login_page_description');
            $table->string('login_page_general_message');
            $table->string('system_expired_message');
            $table->string('invoice_footer');
            $table->boolean('tour_toggle')->default(true);
            $table->timestamps();
            $table->string('captch_site_key', 255)->nullable();
            $table->boolean('login_vehicle_registration')->nullable()->default(false);
            $table->text('landingPage_settings');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('site_settings');
    }
};
