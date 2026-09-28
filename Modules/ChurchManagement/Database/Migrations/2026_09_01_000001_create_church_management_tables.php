<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Church Management — core tables.
 *
 * Every table is prefixed chc_, so the module's data is identifiable at a glance
 * in a database shared with a hundred other modules, and can be dropped as a
 * set.
 *
 * NO FOREIGN KEYS, matching the other vertical modules here. Relationships are
 * enforced in application code instead, so installing onto a tenant that
 * carries legacy or partial data cannot fail on a constraint. The indexes that
 * matter for lookups are declared.
 */
return new class extends Migration
{
    private string $prefix = 'chc_';

    public function up(): void
    {
        $this->createFamilies();
        $this->createMembers();
        $this->createSettings();
    }

    /**
     * Families are created before members because a member points at one.
     */
    private function createFamilies(): void
    {
        $table = $this->prefix . 'families';

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->unsignedInteger('business_location_id')->nullable()->index();

            $t->string('family_code', 50)->nullable();
            $t->string('family_name', 191);

            // The member who represents the household. Nullable because a family
            // is often created first and its head chosen from its members after.
            $t->unsignedBigInteger('head_member_id')->nullable()->index();

            $t->string('phone', 50)->nullable();
            $t->string('email', 191)->nullable();
            $t->text('address')->nullable();
            $t->string('city', 100)->nullable();

            $t->text('notes')->nullable();
            $t->boolean('is_active')->default(true);

            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['business_id', 'family_code'], 'chc_fam_biz_code_idx');
            $t->index(['business_id', 'family_name'], 'chc_fam_biz_name_idx');
            $t->index(['business_id', 'is_active'], 'chc_fam_biz_active_idx');
        });
    }

    private function createMembers(): void
    {
        $table = $this->prefix . 'members';

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->unsignedInteger('business_location_id')->nullable()->index();

            $t->string('member_code', 50)->nullable();

            $t->string('first_name', 100);
            $t->string('last_name', 100)->nullable();

            /*
             | Held as a stored column rather than assembled on every read, so
             | the list can sort and search on one indexed value instead of a
             | CONCAT that no index can serve.
             */
            $t->string('full_name', 191)->nullable();

            $t->unsignedBigInteger('family_id')->nullable()->index();

            /*
             | Relationship to the head of the family - head, spouse, child and
             | so on. A plain string rather than a lookup table: congregations
             | describe these differently and a fixed list would be wrong for
             | most of them.
             */
            $t->string('family_role', 50)->nullable();

            $t->enum('gender', ['male', 'female', 'other'])->nullable();
            $t->date('date_of_birth')->nullable();
            $t->enum('marital_status', ['single', 'married', 'widowed', 'divorced', 'other'])->nullable();

            $t->string('phone', 50)->nullable();
            $t->string('whatsapp', 50)->nullable();
            $t->string('email', 191)->nullable();
            $t->text('address')->nullable();
            $t->string('city', 100)->nullable();

            // Membership lifecycle.
            $t->date('joined_date')->nullable();
            $t->date('baptism_date')->nullable();
            $t->date('confirmation_date')->nullable();

            /*
             | member  - a full member of the congregation
             | visitor - attending but not yet a member
             | inactive- on the roll but no longer attending
             | departed- moved away, transferred out, or deceased
             |
             | Kept as a status rather than deleting the row: a congregation's
             | history matters, and a departed member may return.
             */
            $t->enum('membership_status', ['member', 'visitor', 'inactive', 'departed'])
                ->default('member');

            $t->string('occupation', 191)->nullable();
            $t->text('notes')->nullable();
            $t->string('photo', 255)->nullable();

            $t->boolean('is_active')->default(true);

            $t->unsignedInteger('created_by')->nullable();
            $t->unsignedInteger('updated_by')->nullable();
            $t->timestamps();
            $t->softDeletes();

            $t->index(['business_id', 'member_code'], 'chc_mem_biz_code_idx');
            $t->index(['business_id', 'full_name'], 'chc_mem_biz_name_idx');
            $t->index(['business_id', 'membership_status'], 'chc_mem_biz_status_idx');
            $t->index(['business_id', 'family_id'], 'chc_mem_biz_family_idx');
            $t->index(['business_id', 'is_active'], 'chc_mem_biz_active_idx');
        });
    }

    /**
     * One row per business. Key/value rather than a wide table so a later phase
     * can add a setting without a migration on every tenant.
     */
    private function createSettings(): void
    {
        $table = $this->prefix . 'settings';

        if (Schema::hasTable($table)) {
            return;
        }

        Schema::create($table, function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->unsignedInteger('business_id')->index();
            $t->string('setting_key', 100);
            $t->text('setting_value')->nullable();
            $t->timestamps();

            $t->unique(['business_id', 'setting_key'], 'chc_set_biz_key_unique');
        });
    }

    public function down(): void
    {
        /*
         | Drops only this module's own tables, in reverse dependency order.
         | Nothing outside the chc_ prefix is named anywhere in this file.
         */
        Schema::dropIfExists($this->prefix . 'settings');
        Schema::dropIfExists($this->prefix . 'members');
        Schema::dropIfExists($this->prefix . 'families');
    }
};
