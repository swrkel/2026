<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLawAdminWorkflowTables extends Migration
{
    public function up()
    {
        Schema::create('law_reminders', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('client_id')->nullable()->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->unsignedBigInteger('user_id')->nullable()->index();
            $t->string('title');
            $t->dateTime('remind_at')->index();
            $t->string('channel', 30)->default('in_app');
            $t->string('status', 30)->default('pending')->index();
            $t->string('repeat_rule', 80)->nullable();
            $t->string('source_type', 80)->nullable();
            $t->unsignedBigInteger('source_id')->nullable()->index();
            $t->text('notes')->nullable();
            $t->dateTime('sent_at')->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_document_templates', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->string('name');
            $t->string('category', 100)->nullable()->index();
            $t->text('description')->nullable();
            $t->longText('body_html');
            $t->longText('variables_json')->nullable();
            $t->boolean('active')->default(true)->index();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_communications', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('client_id')->nullable()->index();
            $t->unsignedBigInteger('matter_id')->nullable()->index();
            $t->string('channel', 30)->index();
            $t->string('direction', 20)->default('outbound')->index();
            $t->string('recipient')->nullable();
            $t->string('sender')->nullable();
            $t->string('subject')->nullable();
            $t->longText('body')->nullable();
            $t->string('status', 30)->default('logged')->index();
            $t->dateTime('sent_at')->nullable()->index();
            $t->string('external_reference', 150)->nullable();
            $t->unsignedBigInteger('created_by')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('law_conflict_checks', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->string('check_no', 50)->index();
            $t->string('query_name')->index();
            $t->text('identifiers')->nullable();
            $t->unsignedBigInteger('requested_by')->nullable()->index();
            $t->string('result_status', 30)->default('clear')->index();
            $t->text('notes')->nullable();
            $t->dateTime('checked_at')->index();
            $t->timestamps();
            $t->unique(['business_id', 'check_no']);
        });

        Schema::create('law_conflict_matches', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedBigInteger('business_id')->index();
            $t->unsignedBigInteger('conflict_check_id')->index();
            $t->string('source_type', 80)->index();
            $t->unsignedBigInteger('source_id')->nullable()->index();
            $t->string('matched_name')->nullable();
            $t->string('matched_field', 100)->nullable();
            $t->decimal('match_score', 8, 4)->default(0);
            $t->string('relationship', 100)->nullable();
            $t->text('notes')->nullable();
            $t->timestamps();
        });
    }

    public function down()
    {
        foreach (['law_conflict_matches','law_conflict_checks','law_communications','law_document_templates','law_reminders'] as $table) {
            Schema::dropIfExists($table);
        }
    }
}
