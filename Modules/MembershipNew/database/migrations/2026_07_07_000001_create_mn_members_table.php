<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('mn_members')) {
            return;
        }

        Schema::create('mn_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id')->index();
            $table->unsignedInteger('location_id')->nullable()->index();
            $table->string('member_code', 50)->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100)->nullable();
            $table->string('full_name', 255)->nullable();
            $table->string('full_name_second_language', 255)->nullable();
            $table->string('mobile', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('nic', 50)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('joined_on')->nullable();
            $table->text('address')->nullable();
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_id', 'member_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mn_members');
    }
};
