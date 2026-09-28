<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('membership_member_renewals')) {
            Schema::create('membership_member_renewals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('business_id');
                $table->unsignedBigInteger('membership_member_id');
                $table->string('renewal_period', 20);
                $table->unsignedInteger('renewal_cycles');
                $table->date('next_renewal_date');
                $table->decimal('renewal_amount', 15, 2)->nullable();
                $table->unsignedBigInteger('renewed_by');
                $table->timestamps();

                $table->index(['business_id', 'membership_member_id'], 'idx_membership_member_renewals_member');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_member_renewals');
    }
};
