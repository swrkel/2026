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
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->char('surname', 10)->nullable();
            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('username')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->char('language', 7)->default('en');
            $table->char('contact_no', 15)->nullable();
            $table->text('address')->nullable();
            $table->rememberToken();
            $table->unsignedInteger('business_id')->nullable()->index('business_id');
            $table->enum('status', ['active', 'inactive', 'terminated'])->default('active');
            $table->unsignedInteger('crm_contact_id')->nullable()->index('crm_contact_id');
            $table->boolean('is_cmmsn_agnt')->default(false);
            $table->string('commission_type')->nullable();
            $table->decimal('cmmsn_percent', 4)->default(0);
            $table->string('cmmsn_application', 50)->nullable();
            $table->string('cmmsn_units')->nullable();
            $table->boolean('selected_contacts')->default(false);
            $table->date('dob')->nullable();
            $table->enum('marital_status', ['married', 'unmarried', 'divorced'])->nullable();
            $table->char('blood_group', 10)->nullable();
            $table->char('contact_number', 20)->nullable();
            $table->integer('contact_number_code')->nullable();
            $table->string('fb_link')->nullable();
            $table->string('twitter_link')->nullable();
            $table->string('social_media_1')->nullable();
            $table->string('social_media_2')->nullable();
            $table->text('permanent_address')->nullable();
            $table->text('current_address')->nullable();
            $table->string('guardian_name')->nullable();
            $table->string('custom_field_1')->nullable();
            $table->string('custom_field_2')->nullable();
            $table->string('custom_field_3')->nullable();
            $table->string('custom_field_4')->nullable();
            $table->longText('bank_details')->nullable();
            $table->string('id_proof_name')->nullable();
            $table->string('id_proof_number')->nullable();
            $table->string('crm_department', 255)->nullable()->comment('Contact person\'s department');
            $table->string('crm_designation', 255)->nullable()->comment('Contact person\'s designation');
            $table->boolean('toggle_popup')->default(false);
            $table->text('user_store')->nullable();
            $table->boolean('is_customer')->default(false);
            $table->boolean('is_pump_operator')->default(false);
            $table->unsignedInteger('pump_operator_id')->default(0)->index('pump_operator_id');
            $table->string('pump_operator_passcode')->nullable();
            $table->boolean('is_property_user')->default(false);
            $table->string('property_user_passcode')->nullable();
            $table->boolean('is_superadmin_default')->default(false);
            $table->string('give_away_gifts')->nullable();
            $table->integer('employee_id')->nullable()->index('employee_id');
            $table->softDeletes();
            $table->timestamps();
            $table->boolean('lock_screen')->default(false);
            $table->decimal('max_sales_discount_percent', 15)->nullable();
            $table->integer('pump_operator_pass_changed')->default(0);
            $table->string('designation', 190)->nullable();
            $table->string('profile_photo', 45)->nullable();
            $table->boolean('member')->nullable()->default(false);

            $table->index(['business_id'], 'users_business_id_foreign');
            $table->index(['crm_contact_id'], 'users_crm_contact_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
};
