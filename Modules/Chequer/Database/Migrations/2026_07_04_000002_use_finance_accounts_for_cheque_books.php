<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('cheq_cheque_books') && !Schema::hasColumn('cheq_cheque_books', 'account_id')) {
            Schema::table('cheq_cheque_books', function (Blueprint $table) {
                $table->unsignedBigInteger('account_id')->nullable()->after('business_id')->index();
            });
        }

        if (Schema::hasTable('cheq_cheque_books') && Schema::hasColumn('cheq_cheque_books', 'cheq_bank_account_id') && Schema::hasColumn('cheq_cheque_books', 'account_id')) {
            DB::statement("UPDATE cheq_cheque_books SET account_id = cheq_bank_account_id WHERE account_id IS NULL AND cheq_bank_account_id IS NOT NULL");
        }
    }

    public function down(): void
    {
        // Do not drop account_id to avoid breaking cheque books after migration.
    }
};
