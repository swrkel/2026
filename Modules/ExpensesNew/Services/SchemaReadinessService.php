<?php

namespace Modules\ExpensesNew\Services;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SchemaReadinessService
{
    /*
     * IS1991: bumped from EXPNEW_CORE_SCHEMA_20260730_IS1824_01.
     *
     * isInstalled() returns true as soon as this exact string is present in
     * expnew_schema_versions, and then createCoreTables() and
     * repairCoreColumns() are both skipped. A tenant already carrying the old
     * version would therefore never receive the new prefixes table or the
     * category columns repaired below. Changing the string is what makes those
     * two run once more; both are idempotent, so nothing is rebuilt.
     */
    /*
     * LA-1204: bumped from EXPNEW_CORE_SCHEMA_20260811_IS1991_01.
     *
     * isInstalled() returns true as soon as this exact string is recorded in
     * expnew_schema_versions, and then createCoreTables() and
     * repairCoreColumns() are both skipped. A tenant already carrying the old
     * string would never receive applicable_tax or vat_invoice. Changing it is
     * what makes the repair run once more; it is idempotent, so nothing is
     * rebuilt.
     */
    public const VERSION = 'EXPNEW_CORE_SCHEMA_20260906_IS2201_01';

    /** @var array<string, bool> */
    protected static array $readyConnections = [];

    /**
     * Ensure the standalone Expenses New core schema exists on the active
     * tenant database. The tenant context middleware must run before this
     * service so DB::connection() points to the correct tenant database.
     */
    public function ensure(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();
        $connectionKey = $connection->getName() . '|' . $database;

        if (isset(self::$readyConnections[$connectionKey])) {
            return;
        }

        $schema = $connection->getSchemaBuilder();

        try {
            if ($this->isInstalled($connection, $schema)) {
                self::$readyConnections[$connectionKey] = true;
                return;
            }

            $this->createCoreTables($schema);
            $this->repairCoreColumns($schema);
            $this->seedGlobalDefaults($connection);
            $this->recordInstalledVersion($connection);

            self::$readyConnections[$connectionKey] = true;
        } catch (Throwable $exception) {
            Log::error('Expenses New schema readiness failed.', [
                'connection' => $connection->getName(),
                'database' => $database,
                'schema_version' => self::VERSION,
                'exception' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
            ]);

            throw new RuntimeException(
                'Expenses New database tables could not be prepared on database [' . $database . ']. '
                . 'Run Modules/ExpensesNew/Database/sql/00_EXPENSES_NEW_CORE_SCHEMA_IDEMPOTENT.sql '
                . 'on this tenant database and reload the page.',
                0,
                $exception
            );
        }
    }

    protected function isInstalled(Connection $connection, Builder $schema): bool
    {
        if (! $schema->hasTable('expnew_schema_versions')) {
            return false;
        }

        if (! $schema->hasTable('expnew_expenses')) {
            return false;
        }

        return $connection->table('expnew_schema_versions')
            ->where('version', self::VERSION)
            ->exists();
    }

    protected function createCoreTables(Builder $schema): void
    {
        $this->createIfMissing($schema, 'expnew_schema_versions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('version', 100)->unique('expnew_schema_version_unique');
            $table->timestamp('installed_at')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing($schema, 'expnew_expense_accounts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_accounts_business_idx');
            $table->unsignedBigInteger('external_account_id')->nullable();
            $table->string('name', 191);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'is_active'], 'expnew_accounts_active_idx');
            $table->index(['business_id', 'code'], 'expnew_accounts_code_idx');
            $table->index(['business_id', 'external_account_id'], 'expnew_accounts_external_idx');
        });

        $this->createIfMissing($schema, 'expnew_payees', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_payees_business_idx');
            $table->unsignedBigInteger('external_payee_id')->nullable();
            $table->string('name', 191);
            $table->string('mobile', 50)->nullable();
            $table->string('email', 191)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'is_active'], 'expnew_payees_active_idx');
            $table->index(['business_id', 'name'], 'expnew_payees_name_idx');
            $table->index(['business_id', 'external_payee_id'], 'expnew_payees_external_idx');
        });

        $this->createIfMissing($schema, 'expnew_categories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_categories_business_idx');
            $table->string('name', 191);
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->unsignedBigInteger('default_payee_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'is_active'], 'expnew_categories_active_idx');
            $table->index(['business_id', 'code'], 'expnew_categories_code_idx');
        });

        $this->createIfMissing($schema, 'expnew_category_codes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_cat_codes_business_idx');
            $table->string('code', 50);
            $table->string('name', 191);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'code'], 'expnew_cat_codes_unique');
        });

        /*
         * IS1991 (#1): the Prefix List table.
         *
         * Registered here as well as in a migration on purpose. This service is
         * what provisions a tenant at runtime, and isInstalled() short-circuits
         * on the recorded VERSION - so a tenant that never runs `artisan
         * migrate` would otherwise never get this table and the Prefix List
         * would fail on a query for a table that does not exist.
         */
        $this->createIfMissing($schema, 'expnew_expense_prefixes', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_expense_prefixes_business_idx');
            $table->string('prefix', 20);
            $table->string('starting_no', 12)->nullable();
            $table->string('code_date', 32)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'prefix'], 'expnew_expense_prefixes_unique');
        });

        $this->createIfMissing($schema, 'expnew_settings', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_settings_business_idx');
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'key'], 'expnew_settings_unique');
        });

        $this->createIfMissing($schema, 'expnew_expenses', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_expenses_business_idx');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('expense_no', 100);
            $table->date('expense_date');
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('payee_id')->nullable();
            $table->unsignedBigInteger('expense_account_id')->nullable();
            $table->string('accounting_module', 50)->nullable();
            $table->decimal('total_amount', 22, 4)->default(0);
            $table->decimal('paid_amount', 22, 4)->default(0);
            $table->decimal('due_amount', 22, 4)->default(0);
            $table->decimal('balance_amount', 22, 4)->default(0);
            $table->string('payment_status', 30)->default('due');
            $table->string('payment_method', 40)->nullable();
            $table->string('reference_no', 191)->nullable();
            $table->string('cheque_no', 191)->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('card_no', 191)->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 40)->default('active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'expense_no'], 'expnew_expense_no_unique');
            $table->index(['business_id', 'expense_date'], 'expnew_expenses_date_idx');
            $table->index(['business_id', 'status'], 'expnew_expenses_status_idx');
            $table->index(['business_id', 'payment_status'], 'expnew_expenses_payment_idx');
            $table->index('category_id', 'expnew_expenses_category_idx');
            $table->index('payee_id', 'expnew_expenses_payee_idx');
        });

        $this->createIfMissing($schema, 'expnew_expense_payments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_payments_business_idx');
            $table->unsignedBigInteger('expense_id')->index('expnew_payments_expense_idx');
            $table->date('payment_date')->nullable();
            $table->string('method', 40)->default('cash');
            $table->decimal('amount', 22, 4)->default(0);
            $table->string('reference_no', 191)->nullable();
            $table->string('cheque_no', 191)->nullable();
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('card_no', 191)->nullable();
            $table->string('sw_shift_no', 60)->nullable()->index('expnew_payments_sw_shift_idx');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'payment_date'], 'expnew_payments_date_idx');
        });

        $this->createIfMissing($schema, 'expnew_expense_attachments', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_attach_business_idx');
            $table->unsignedBigInteger('expense_id')->index('expnew_attach_expense_idx');
            $table->string('file_name', 255);
            $table->string('file_path', 500);
            $table->string('file_type', 191)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing($schema, 'expnew_status_histories', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_status_business_idx');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('expense_id')->index('expnew_status_expense_idx');
            $table->string('old_status', 40)->nullable();
            $table->string('new_status', 40);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamps();
        });

        $this->createIfMissing($schema, 'expnew_activity_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_activity_business_idx');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('subject_type', 191)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('event', 100);
            $table->text('description')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id'], 'expnew_activity_subject_idx');
        });

        $this->createIfMissing($schema, 'expnew_approval_workflows', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_workflow_business_idx');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('name', 191);
            $table->string('code', 100)->nullable();
            $table->json('rules_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->index(['business_id', 'location_id', 'is_active'], 'expnew_workflow_active_idx');
        });

        $this->createIfMissing($schema, 'expnew_approval_levels', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('workflow_id')->index('expnew_levels_workflow_idx');
            $table->unsignedInteger('level_no')->default(1);
            $table->string('name', 191)->nullable();
            $table->decimal('min_amount', 22, 4)->nullable();
            $table->decimal('max_amount', 22, 4)->nullable();
            $table->boolean('is_required')->default(true);
            $table->json('approver_user_ids')->nullable();
            $table->json('approver_role_ids')->nullable();
            $table->timestamps();
            $table->unique(['workflow_id', 'level_no'], 'expnew_levels_unique');
        });

        $this->createIfMissing($schema, 'expnew_approval_logs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index('expnew_appr_logs_business_idx');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('expense_id')->index('expnew_appr_logs_expense_idx');
            $table->unsignedBigInteger('workflow_id')->nullable();
            $table->unsignedInteger('level_no')->nullable();
            $table->string('action', 50);
            $table->decimal('amount', 22, 4)->default(0);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('action_by')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });

        $this->createMasterTable($schema, 'expnew_departments', false);
        $this->createMasterTable($schema, 'expnew_cost_centers', false);
        $this->createMasterTable($schema, 'expnew_projects', true);

        $this->createIfMissing($schema, 'expnew_command_widgets', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('widget_key', 100);
            $table->string('widget_title', 191);
            $table->string('widget_type', 50)->default('kpi');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['business_id', 'widget_key'], 'expnew_cmd_widget_unique');
        });

        $this->createIfMissing($schema, 'expnew_command_preferences', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->string('workspace_key', 100);
            $table->json('preferences')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'business_id', 'workspace_key'], 'expnew_cmd_pref_unique');
        });

        $this->createIfMissing($schema, 'expnew_operation_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('event_type', 100);
            $table->string('event_title', 191);
            $table->text('event_message')->nullable();
            $table->dateTime('event_time');
            $table->string('severity', 30)->default('info');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->index('event_time', 'expnew_ops_event_time');
            $table->index(['business_id', 'location_id'], 'expnew_ops_business');
        });

        $this->createIfMissing($schema, 'expnew_approval_queues', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->unsignedBigInteger('expense_id')->nullable();
            $table->string('expense_no', 100)->nullable();
            $table->decimal('amount', 22, 4)->default(0);
            $table->integer('current_level')->default(1);
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('assigned_role', 100)->nullable();
            $table->integer('priority')->default(0);
            $table->string('status', 50)->default('pending');
            $table->string('last_action', 50)->nullable();
            $table->unsignedBigInteger('last_action_by')->nullable();
            $table->dateTime('last_action_at')->nullable();
            $table->text('last_comments')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'priority'], 'expnew_appr_status');
            $table->index(['business_id', 'location_id'], 'expnew_appr_business');
        });

        $this->createIfMissing($schema, 'expnew_command_alerts', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->nullable();
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('alert_type', 100);
            $table->string('alert_title', 191);
            $table->text('alert_message')->nullable();
            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('severity', 30)->default('warning');
            $table->boolean('is_resolved')->default(false);
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['alert_type', 'is_resolved'], 'expnew_alert_active');
            $table->index(['business_id', 'location_id'], 'expnew_alert_business');
        });

        $this->createIfMissing($schema, 'expnew_saved_filters', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('filter_name', 191);
            $table->string('filter_context', 100);
            $table->json('filter_payload')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    protected function createMasterTable(Builder $schema, string $tableName, bool $withDates): void
    {
        $this->createIfMissing($schema, $tableName, function (Blueprint $table) use ($tableName, $withDates): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('business_id')->index(substr($tableName . '_business_idx', 0, 60));
            $table->unsignedBigInteger('location_id')->nullable();
            $table->string('code', 100)->nullable();
            $table->string('name', 191);
            $table->text('description')->nullable();
            if ($withDates) {
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
            }
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    protected function repairCoreColumns(Builder $schema): void
    {
        $columns = [
            'location_id' => fn (Blueprint $table) => $table->unsignedBigInteger('location_id')->nullable(),
            'expense_no' => fn (Blueprint $table) => $table->string('expense_no', 100)->nullable(),
            'expense_date' => fn (Blueprint $table) => $table->date('expense_date')->nullable(),
            'category_id' => fn (Blueprint $table) => $table->unsignedBigInteger('category_id')->nullable(),
            'payee_id' => fn (Blueprint $table) => $table->unsignedBigInteger('payee_id')->nullable(),
            'expense_account_id' => fn (Blueprint $table) => $table->unsignedBigInteger('expense_account_id')->nullable(),
            'accounting_module' => fn (Blueprint $table) => $table->string('accounting_module', 50)->nullable(),
            'total_amount' => fn (Blueprint $table) => $table->decimal('total_amount', 22, 4)->default(0),
            'paid_amount' => fn (Blueprint $table) => $table->decimal('paid_amount', 22, 4)->default(0),
            'due_amount' => fn (Blueprint $table) => $table->decimal('due_amount', 22, 4)->default(0),
            'balance_amount' => fn (Blueprint $table) => $table->decimal('balance_amount', 22, 4)->default(0),
            'payment_status' => fn (Blueprint $table) => $table->string('payment_status', 30)->default('due'),
            'payment_method' => fn (Blueprint $table) => $table->string('payment_method', 40)->nullable(),
            'reference_no' => fn (Blueprint $table) => $table->string('reference_no', 191)->nullable(),
            'cheque_no' => fn (Blueprint $table) => $table->string('cheque_no', 191)->nullable(),
            'bank_account_id' => fn (Blueprint $table) => $table->unsignedBigInteger('bank_account_id')->nullable(),
            'card_no' => fn (Blueprint $table) => $table->string('card_no', 191)->nullable(),
            'notes' => fn (Blueprint $table) => $table->text('notes')->nullable(),
            'status' => fn (Blueprint $table) => $table->string('status', 40)->default('active'),
            'created_by' => fn (Blueprint $table) => $table->unsignedBigInteger('created_by')->nullable(),
            'updated_by' => fn (Blueprint $table) => $table->unsignedBigInteger('updated_by')->nullable(),
            'created_at' => fn (Blueprint $table) => $table->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $table) => $table->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            $this->addColumnIfMissing($schema, 'expnew_expenses', $column, $definition);
        }

        /*
         * IS1991: columns added by migration that this service never knew about.
         *
         * THIS IS WHY THE CATEGORY CHECKBOXES SAVED NOTHING on some tenants.
         *
         * createCoreTables() builds expnew_categories with its original ten
         * columns. The three flags arrived later, by migration
         * (2026_08_06_000002), and this service was never told - so any tenant
         * provisioned at runtime rather than through `artisan migrate` had a
         * categories table with no flag columns at all.
         *
         * CategoryController::categoryFlags() guards every write with
         * Schema::hasColumn, which is correct and is what stopped it erroring -
         * but the visible result is a checkbox that ticks, saves, and comes back
         * empty, with nothing in the log. Exactly the reported symptom.
         *
         * The same applies to the three expense columns from
         * 2026_08_07_000002, which drive the Add Expenses dropdowns.
         *
         * Repairs are idempotent, so this is safe on a tenant that DID migrate.
         */
        $categoryColumns = [
            'vat_input_claimed' => fn (Blueprint $table) => $table->boolean('vat_input_claimed')->default(0),
            'is_sub_category' => fn (Blueprint $table) => $table->boolean('is_sub_category')->default(0),
            'is_employee' => fn (Blueprint $table) => $table->boolean('is_employee')->default(0),
            // IS1991 (#2): the selection behind each of the two flags.
            'parent_id' => fn (Blueprint $table) => $table->unsignedBigInteger('parent_id')->nullable(),
            'employee_id' => fn (Blueprint $table) => $table->unsignedBigInteger('employee_id')->nullable(),
        ];

        foreach ($categoryColumns as $column => $definition) {
            $this->addColumnIfMissing($schema, 'expnew_categories', $column, $definition);
        }

        $expenseCategoryColumns = [
            'vat_category_id' => fn (Blueprint $table) => $table->unsignedBigInteger('vat_category_id')->nullable(),
            'sub_category_id' => fn (Blueprint $table) => $table->unsignedBigInteger('sub_category_id')->nullable(),
            'employee_id' => fn (Blueprint $table) => $table->unsignedBigInteger('employee_id')->nullable(),
            /*
             * LA-1204: saving an expense failed with
             *     SQLSTATE[42S22]: Unknown column 'applicable_tax'
             *
             * These two arrived in 2026_08_03_000001_add_vat_fields_to_expnew_
             * expenses_table.php, and this service was never told about them -
             * the SAME omission that IS1991 recorded for the three columns above.
             * A tenant provisioned at runtime, or one whose recorded schema
             * version already matched, therefore never received them, and the
             * insert in CreatesExpenses names both unconditionally.
             *
             * Definitions match the migration exactly, defaults included, so a
             * tenant repaired here ends up identical to one that migrated:
             * applicable_tax is 'none' and vat_invoice is 0 for existing rows.
             *
             * The lesson is worth stating plainly: every migration that adds a
             * column to a core table must also be reflected here, or the feature
             * works on migrated tenants and fails on provisioned ones.
             */
            'applicable_tax' => fn (Blueprint $table) => $table->string('applicable_tax', 20)->nullable()->default('none'),
            'vat_invoice' => fn (Blueprint $table) => $table->boolean('vat_invoice')->nullable()->default(0),
        ];

        foreach ($expenseCategoryColumns as $column => $definition) {
            $this->addColumnIfMissing($schema, 'expnew_expenses', $column, $definition);
        }

        // IS2201: runtime-provisioned Expenses New tenants need the same
        // optional SW shift link as migrated tenants.
        $this->addColumnIfMissing(
            $schema,
            'expnew_expense_payments',
            'sw_shift_no',
            fn (Blueprint $table) => $table->string('sw_shift_no', 60)
                ->nullable()
                ->index('expnew_payments_sw_shift_idx')
        );

        if ($schema->hasTable('expnew_expenses')
            && $schema->hasColumn('expnew_expenses', 'due_amount')
            && $schema->hasColumn('expnew_expenses', 'balance_amount')) {
            DB::table('expnew_expenses')
                ->where(function ($query): void {
                    $query->whereNull('balance_amount')
                        ->orWhereColumn('balance_amount', '!=', 'due_amount');
                })
                ->update(['balance_amount' => DB::raw('due_amount')]);
        }
    }

    protected function seedGlobalDefaults(Connection $connection): void
    {
        $now = now();
        $widgets = [
            ['today_expenses', 'Today Expenses', 'kpi', 10],
            ['pending_approvals', 'Pending Approvals', 'kpi', 20],
            ['pending_payments', 'Pending Payments', 'kpi', 30],
            ['budget_alerts', 'Budget Alerts', 'alert', 40],
        ];

        foreach ($widgets as [$key, $title, $type, $sortOrder]) {
            $exists = $connection->table('expnew_command_widgets')
                ->whereNull('business_id')
                ->where('widget_key', $key)
                ->exists();

            if (! $exists) {
                $connection->table('expnew_command_widgets')->insert([
                    'business_id' => null,
                    'widget_key' => $key,
                    'widget_title' => $title,
                    'widget_type' => $type,
                    'settings' => null,
                    'is_active' => 1,
                    'sort_order' => $sortOrder,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    protected function recordInstalledVersion(Connection $connection): void
    {
        $now = now();

        $connection->table('expnew_schema_versions')->updateOrInsert(
            ['version' => self::VERSION],
            ['installed_at' => $now, 'created_at' => $now, 'updated_at' => $now]
        );
    }

    protected function createIfMissing(Builder $schema, string $tableName, Closure $definition): void
    {
        if ($schema->hasTable($tableName)) {
            return;
        }

        try {
            $schema->create($tableName, $definition);
        } catch (Throwable $exception) {
            // A second simultaneous request may have created the table after
            // the initial hasTable check. Treat that race as success.
            if (! $schema->hasTable($tableName)) {
                throw $exception;
            }
        }
    }

    protected function addColumnIfMissing(
        Builder $schema,
        string $tableName,
        string $columnName,
        Closure $definition
    ): void {
        if (! $schema->hasTable($tableName) || $schema->hasColumn($tableName, $columnName)) {
            return;
        }

        try {
            $schema->table($tableName, function (Blueprint $table) use ($definition): void {
                $definition($table);
            });
        } catch (Throwable $exception) {
            if (! $schema->hasColumn($tableName, $columnName)) {
                throw $exception;
            }
        }
    }
}
