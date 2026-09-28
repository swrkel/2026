<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('distribution_stock_transfers', 'to_store_id')) {
            Schema::table('distribution_stock_transfers', function (Blueprint $table) {
                $table->unsignedBigInteger('to_store_id')->nullable()->after('store_id');
                $table->index('to_store_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('distribution_stock_transfers', 'to_store_id')) {
            Schema::table('distribution_stock_transfers', function (Blueprint $table) {
                $table->dropIndex(['to_store_id']);
                $table->dropColumn('to_store_id');
            });
        }
    }
};
