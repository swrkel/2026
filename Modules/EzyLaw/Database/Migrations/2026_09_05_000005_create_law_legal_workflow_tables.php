<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLawLegalWorkflowTables extends Migration
{
    public function up()
    {
        Schema::create('law_chronology_entries', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('matter_id')->index();
            $t->dateTime('event_at')->index();
            $t->string('event_type', 80)->default('general')->index();
            $t->string('title');
            $t->text('description')->nullable();
            $t->string('source_type', 80)->nullable();
            $t->unsignedBigInteger('source_id')->nullable()->index();
            $t->boolean('is_key_event')->default(false)->index();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_retainers', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('client_id')->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->string('retainer_no', 50)->index();
            $t->date('agreement_date')->index();
            $t->date('start_date')->nullable()->index();
            $t->date('end_date')->nullable()->index();
            $t->string('retainer_type', 50)->default('general');
            $t->decimal('agreed_amount', 22, 4)->default(0);
            $t->decimal('replenishment_threshold', 22, 4)->default(0);
            $t->string('status', 30)->default('active')->index();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
            $t->unique(['business_id', 'retainer_no']);
        });

        Schema::create('law_trust_accounts', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('location_id')->nullable()->index();
            $t->string('name');
            $t->string('account_no', 100)->nullable();
            $t->string('bank_name')->nullable();
            $t->string('currency', 10)->nullable();
            $t->decimal('current_balance', 22, 4)->default(0);
            $t->boolean('active')->default(true)->index();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_trust_transactions', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('trust_account_id')->index();
            $t->unsignedBigInteger('client_id')->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->unsignedBigInteger('invoice_id')->nullable()->index();
            $t->date('transaction_date')->index();
            $t->string('type', 50)->index();
            $t->string('direction', 10)->index();
            $t->decimal('amount', 22, 4);
            $t->string('reference', 100)->nullable();
            $t->string('payee')->nullable();
            $t->text('description')->nullable();
            $t->decimal('running_balance', 22, 4)->default(0);
            $t->string('finance_sync_status', 30)->default('pending')->index();
            $t->string('finance_reference', 100)->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });
    }

    public function down()
    {
        foreach (['law_trust_transactions','law_trust_accounts','law_retainers','law_chronology_entries'] as $table) {
            Schema::dropIfExists($table);
        }
    }
}
