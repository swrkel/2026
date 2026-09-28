<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
class CreateSubsCentralSubscriptionTables extends Migration
{
    protected function schema()
    {
        $connection = !empty(config('database.connections.system.database')) ? 'system' : (string) config('tenancy.database.central_connection', config('database.default'));
        return Schema::connection($connection);
    }
    public function up()
    {
        $schema = $this->schema();
        if (!$schema->hasTable('subs_business_subscriptions')) {
            $schema->create('subs_business_subscriptions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('tenant_id', 191)->index();
                $table->string('tenant_database', 191)->nullable();
                $table->unsignedBigInteger('business_registry_id')->nullable()->index();
                $table->string('business_global_uid', 191)->nullable()->index();
                $table->string('business_name', 255);
                $table->date('business_registered_on');
                $table->unsignedInteger('subscription_period_days');
                $table->decimal('subscription_amount', 22, 4)->default(0);
                $table->text('business_mobile_numbers');
                $table->date('expiry_date')->index();
                $table->boolean('status')->default(1)->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
        if (!$schema->hasTable('subs_subscription_reminders')) {
            $schema->create('subs_subscription_reminders', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('subscription_id')->index();
                $table->unsignedTinyInteger('reminder_no');
                $table->unsignedInteger('days_before')->nullable();
                $table->text('message_body')->nullable();
                $table->boolean('is_enabled')->default(0);
                $table->timestamps();
                $table->unique(['subscription_id', 'reminder_no'], 'subs_reminder_unique');
            });
        }
        if (!$schema->hasTable('subs_reminder_logs')) {
            $schema->create('subs_reminder_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('subscription_id')->index();
                $table->unsignedBigInteger('reminder_id')->nullable()->index();
                $table->date('due_date')->index();
                $table->text('mobile_numbers')->nullable();
                $table->text('message_body')->nullable();
                $table->string('status', 30)->default('sent')->index();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }
        if (!$schema->hasTable('subs_master_settings')) {
            $schema->create('subs_master_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('setting_key', 191)->unique();
                $table->text('setting_value')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }
    public function down()
    {
        $schema = $this->schema();
        $schema->dropIfExists('subs_reminder_logs');
        $schema->dropIfExists('subs_subscription_reminders');
        $schema->dropIfExists('subs_business_subscriptions');
        $schema->dropIfExists('subs_master_settings');
    }
}
