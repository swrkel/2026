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
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            // Add parent_sell_line_id if it doesn't exist
            if (!Schema::hasColumn('transaction_sell_lines', 'parent_sell_line_id')) {
                $table->integer('parent_sell_line_id')->nullable();
            }

            // Add children_type if it doesn't exist
            if (!Schema::hasColumn('transaction_sell_lines', 'children_type')) {
                $table->string('children_type', 50)->nullable();
            }

            // Add line_total if it doesn't exist
            if (!Schema::hasColumn('transaction_sell_lines', 'line_total')) {
                $table->decimal('line_total', 10, 2)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('transaction_sell_lines', function (Blueprint $table) {
            // Drop columns if they exist
            if (Schema::hasColumn('transaction_sell_lines', 'parent_sell_line_id')) {
                $table->dropColumn('parent_sell_line_id');
            }

            if (Schema::hasColumn('transaction_sell_lines', 'children_type')) {
                $table->dropColumn('children_type');
            }

            if (Schema::hasColumn('transaction_sell_lines', 'line_total')) {
                $table->dropColumn('line_total');
            }
        });
    }
};
