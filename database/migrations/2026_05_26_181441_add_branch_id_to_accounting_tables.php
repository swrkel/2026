<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tables that should support branch-wise accounting.
     *
     * @var array
     */
    protected array $tables = [
        'accounts',
        'transactions',
        'transaction_payments',
        'account_transactions',
        'journal_entries',
        'ledgers',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'branch_id')) {
                    $table->unsignedBigInteger('branch_id')
                        ->nullable()
                        ->after('business_id')
                        ->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'branch_id')) {
                    $table->dropIndex([$tableName . '_branch_id_index']);
                    $table->dropColumn('branch_id');
                }
            });
        }
    }
};