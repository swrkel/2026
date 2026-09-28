<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stnew_stock_transfer_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('stnew_stock_transfer_settings', 'require_stock_before_dispatch')) $table->boolean('require_stock_before_dispatch')->default(false)->after('allow_partial_receive');
            if (!Schema::hasColumn('stnew_stock_transfer_settings', 'auto_generate_document_numbers')) $table->boolean('auto_generate_document_numbers')->default(true)->after('require_stock_before_dispatch');
        });
        if (!Schema::hasTable('stnew_stock_transfer_alerts')) {
            Schema::create('stnew_stock_transfer_alerts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('transfer_id')->index();
                $table->string('event')->index();
                $table->text('message')->nullable();
                $table->unsignedBigInteger('target_user_id')->nullable()->index();
                $table->boolean('is_read')->default(false)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down(){Schema::dropIfExists('stnew_stock_transfer_alerts');}
};
