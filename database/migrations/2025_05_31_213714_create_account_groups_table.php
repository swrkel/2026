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
        if (Schema::hasTable('account_groups')) {
            return;
        }
        Schema::create('account_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id')->index('business_id');
            $table->string('name');
            $table->unsignedInteger('account_type_id')->nullable()->index('account_type_id');
            $table->text('note')->nullable();
            $table->unsignedInteger('default_account_group_id')->index('default_account_group_id');
            $table->enum('reg_cheque', ['Y', 'N'])->default('Y');
            $table->integer('show_status')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('account_groups');
    }
};
