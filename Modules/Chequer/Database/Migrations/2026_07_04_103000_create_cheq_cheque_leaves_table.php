<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('cheq_cheque_leaves')) {
            Schema::create('cheq_cheque_leaves', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('cheq_cheque_book_id');
                $table->string('cheque_no', 100);
                $table->string('status', 30)->default('available')->index();
                $table->unsignedBigInteger('cheq_cheque_id')->nullable()->index();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['cheq_cheque_book_id', 'cheque_no'], 'cheq_leaves_book_cheque_unique');
            });
        }

        if (Schema::hasTable('cheq_cheques') && !Schema::hasColumn('cheq_cheques', 'cheq_cheque_leaf_id')) {
            Schema::table('cheq_cheques', function (Blueprint $table) {
                $table->unsignedBigInteger('cheq_cheque_leaf_id')->nullable()->after('cheq_cheque_book_id')->index('cheq_cheques_leaf_id_index');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('cheq_cheques') && Schema::hasColumn('cheq_cheques', 'cheq_cheque_leaf_id')) {
            Schema::table('cheq_cheques', function (Blueprint $table) {
                $table->dropIndex('cheq_cheques_leaf_id_index');
                $table->dropColumn('cheq_cheque_leaf_id');
            });
        }

        Schema::dropIfExists('cheq_cheque_leaves');
    }
};
