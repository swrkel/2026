<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pumper_login_attempt_histories')) {
            Schema::create('pumper_login_attempt_histories', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedInteger('pumper_login_attempt_id')->nullable()->index('plah_attempt_idx');
                $table->unsignedInteger('business_id')->nullable()->index('plah_business_idx');
                $table->unsignedInteger('pump_operator_id')->nullable()->index('plah_operator_idx');
                $table->unsignedInteger('operator_user_id')->nullable();
                $table->string('operator_name')->nullable();
                $table->string('company_number')->nullable();
                $table->string('ip_address')->nullable();
                $table->string('passcode_mask', 32)->nullable();
                $table->unsignedInteger('attempt_count')->default(0);
                $table->dateTime('blocked_at')->nullable()->index('plah_blocked_at_idx');
                $table->dateTime('unblocked_at')->nullable()->index('plah_unblocked_at_idx');
                $table->unsignedInteger('unblocked_by_user_id')->nullable();
                $table->string('unblocked_by_name')->nullable();
                $table->string('source_module', 64)->nullable();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        // Preserve all currently blocked rows when this audit feature is first
        // deployed. Active legacy rows stay visible in the Login Access Records
        // table because old code did not record who unblocked them.
        if (Schema::hasTable('pumper_login_attempts')) {
            DB::table('pumper_login_attempts')
                ->where('status', 'Blocked')
                ->orderBy('id')
                ->chunkById(200, function ($attempts): void {
                    foreach ($attempts as $attempt) {
                        $exists = DB::table('pumper_login_attempt_histories')
                            ->where('pumper_login_attempt_id', $attempt->id)
                            ->whereNull('unblocked_at')
                            ->exists();

                        if ($exists) {
                            continue;
                        }

                        $passcode = trim((string) $attempt->last_entered_passcode);
                        $visible = $passcode === '' ? null : substr($passcode, -2);

                        DB::table('pumper_login_attempt_histories')->insert([
                            'pumper_login_attempt_id' => $attempt->id,
                            'business_id' => $attempt->business_id,
                            'company_number' => $attempt->company_number,
                            'ip_address' => $attempt->ip_address,
                            'passcode_mask' => $visible === null
                                ? null
                                : str_repeat('*', max(2, strlen($passcode) - strlen($visible))) . $visible,
                            'attempt_count' => (int) $attempt->attempt_count,
                            'blocked_at' => $attempt->updated_at ?: $attempt->created_at,
                            'source_module' => 'Legacy Import',
                            'notes' => 'Existing blocked record imported when login history was enabled.',
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pumper_login_attempt_histories');
    }
};
