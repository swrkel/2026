<?php

namespace Modules\SimpleAudit\Services;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SchemaInstaller
{
    public function install($connection = null, $withTriggers = true, $seedStock = true)
    {
        $connection = $connection ?: config('database.default');
        $schema = Schema::connection($connection);

        if (!$schema->hasTable('sau_settings')) {
            $schema->create('sau_settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->default(0);
                $table->string('key_name', 120);
                $table->longText('value_json')->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'key_name'], 'sau_settings_business_key_uq');
            });
        }

        if (!$schema->hasTable('sau_change_events')) {
            $schema->create('sau_change_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->string('source_table', 80)->index();
                $table->unsignedBigInteger('source_id')->nullable()->index();
                $table->string('event_type', 20)->index();
                $table->unsignedBigInteger('transaction_id')->nullable()->index();
                $table->unsignedBigInteger('payment_id')->nullable()->index();
                $table->unsignedBigInteger('contact_id')->nullable()->index();
                $table->unsignedBigInteger('account_id')->nullable()->index();
                $table->unsignedBigInteger('product_id')->nullable()->index();
                $table->unsignedBigInteger('variation_id')->nullable()->index();
                $table->unsignedBigInteger('actor_user_id')->nullable()->index();
                $table->longText('old_data')->nullable();
                $table->longText('new_data')->nullable();
                $table->dateTime('occurred_at')->index();
                $table->timestamp('created_at')->nullable();
                $table->index(['business_id', 'occurred_at'], 'sau_events_business_time_idx');
                $table->index(['business_id', 'location_id', 'occurred_at'], 'sau_events_location_time_idx');
            });
        }

        if (!$schema->hasTable('sau_stock_snapshots')) {
            $schema->create('sau_stock_snapshots', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('location_id')->index();
                $table->unsignedBigInteger('product_id')->index();
                $table->unsignedBigInteger('variation_id')->index();
                $table->decimal('qty_before', 22, 6)->nullable();
                $table->decimal('qty_after', 22, 6)->nullable();
                $table->string('source', 50)->default('variation_location_details');
                $table->unsignedBigInteger('source_row_id')->nullable();
                $table->dateTime('changed_at')->index();
                $table->timestamp('created_at')->nullable();
                $table->index(['business_id', 'location_id', 'product_id', 'variation_id', 'id'], 'sau_stock_lookup_idx');
            });
        }

        if (!$schema->hasTable('sau_report_shares')) {
            $schema->create('sau_report_shares', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('token', 96)->unique();
                $table->string('tenant_id', 191)->nullable()->index();
                $table->unsignedBigInteger('business_id')->index();
                $table->unsignedBigInteger('location_id')->nullable()->index();
                $table->unsignedBigInteger('store_id')->nullable()->index();
                $table->date('date_from');
                $table->date('date_to');
                $table->longText('filters_json')->nullable();
                $table->text('note')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->dateTime('expires_at')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!$schema->hasTable('sau_activity_logs')) {
            $schema->create('sau_activity_logs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('business_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('action', 80)->index();
                $table->string('reference_type', 80)->nullable();
                $table->string('reference_id', 191)->nullable();
                $table->text('note')->nullable();
                $table->longText('meta_json')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }

        if ($withTriggers) {
            $this->installTriggers($connection);
        }

        if ($seedStock) {
            $this->seedStockSnapshots($connection);
        }

        DB::connection($connection)->table('sau_settings')->updateOrInsert(
            ['business_id' => 0, 'key_name' => 'schema_version'],
            ['value_json' => json_encode(['version' => 2]), 'updated_at' => now(), 'created_at' => now()]
        );

        $auditStarted = DB::connection($connection)->table('sau_settings')
            ->where('business_id', 0)->where('key_name', 'audit_tracking_started_at')->exists();
        if (!$auditStarted) {
            DB::connection($connection)->table('sau_settings')->insert([
                'business_id' => 0,
                'key_name' => 'audit_tracking_started_at',
                'value_json' => json_encode(['started_at' => now()->format('Y-m-d H:i:s')]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return true;
    }

    public function seedStockSnapshots($connection = null, $force = false)
    {
        $connection = $connection ?: config('database.default');
        $schema = Schema::connection($connection);
        if (!$schema->hasTable('sau_stock_snapshots') || !$schema->hasTable('variation_location_details') || !$schema->hasTable('products')) {
            return 0;
        }

        foreach (['id','location_id','product_id','variation_id','qty_available'] as $column) {
            if (!$schema->hasColumn('variation_location_details', $column)) {
                return 0;
            }
        }
        if (!$schema->hasColumn('products', 'id') || !$schema->hasColumn('products', 'business_id')) {
            return 0;
        }

        $existing = (int) DB::connection($connection)->table('sau_stock_snapshots')->where('source', 'baseline')->count();
        if ($existing > 0 && !$force) {
            return 0;
        }

        $sql = "INSERT INTO sau_stock_snapshots
            (business_id, location_id, product_id, variation_id, qty_before, qty_after, source, source_row_id, changed_at, created_at)
            SELECT p.business_id, vld.location_id, vld.product_id, vld.variation_id,
                   COALESCE(vld.qty_available,0), COALESCE(vld.qty_available,0),
                   'baseline', vld.id, NOW(), NOW()
              FROM variation_location_details vld
              JOIN products p ON p.id = vld.product_id";
        DB::connection($connection)->statement($sql);

        $stockStarted = DB::connection($connection)->table('sau_settings')
            ->where('business_id', 0)->where('key_name', 'stock_tracking_started_at')->exists();
        if (!$stockStarted) {
            DB::connection($connection)->table('sau_settings')->insert([
                'business_id' => 0,
                'key_name' => 'stock_tracking_started_at',
                'value_json' => json_encode(['started_at' => now()->format('Y-m-d H:i:s')]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) DB::connection($connection)->table('sau_stock_snapshots')->where('source', 'baseline')->count();
    }

    public function installTriggers($connection = null)
    {
        $connection = $connection ?: config('database.default');
        $schema = Schema::connection($connection);

        if ($schema->hasTable('variation_location_details') && $schema->hasTable('products')
            && $this->hasColumns($connection, 'variation_location_details', ['id','location_id','product_id','variation_id','qty_available'])
            && $this->hasColumns($connection, 'products', ['id','business_id'])) {
            $this->replaceTrigger($connection, 'sau_vld_ai', $this->triggerVldInsert());
            $this->replaceTrigger($connection, 'sau_vld_au', $this->triggerVldUpdate());
            $this->replaceTrigger($connection, 'sau_vld_ad', $this->triggerVldDelete());
        }

        if ($schema->hasTable('transactions') && $this->hasColumns($connection, 'transactions', ['id','type'])) {
            $this->replaceTrigger($connection, 'sau_transactions_ai', $this->triggerTransactions($connection, 'INSERT'));
            $this->replaceTrigger($connection, 'sau_transactions_au', $this->triggerTransactions($connection, 'UPDATE'));
            $this->replaceTrigger($connection, 'sau_transactions_ad', $this->triggerTransactions($connection, 'DELETE'));
        }

        if ($schema->hasTable('purchase_lines') && $schema->hasTable('transactions')
            && $this->hasColumns($connection, 'purchase_lines', ['id','transaction_id','product_id','variation_id'])) {
            $this->replaceTrigger($connection, 'sau_purchase_lines_ai', $this->triggerPurchaseLines($connection, 'INSERT'));
            $this->replaceTrigger($connection, 'sau_purchase_lines_au', $this->triggerPurchaseLines($connection, 'UPDATE'));
            $this->replaceTrigger($connection, 'sau_purchase_lines_ad', $this->triggerPurchaseLines($connection, 'DELETE'));
        }

        if ($schema->hasTable('stock_adjustment_lines') && $schema->hasTable('transactions')
            && $this->hasColumns($connection, 'stock_adjustment_lines', ['id','transaction_id','product_id','variation_id'])) {
            $this->replaceTrigger($connection, 'sau_stock_adjustment_lines_ai', $this->triggerStockAdjustmentLines($connection, 'INSERT'));
            $this->replaceTrigger($connection, 'sau_stock_adjustment_lines_au', $this->triggerStockAdjustmentLines($connection, 'UPDATE'));
            $this->replaceTrigger($connection, 'sau_stock_adjustment_lines_ad', $this->triggerStockAdjustmentLines($connection, 'DELETE'));
        }

        if ($schema->hasTable('transaction_payments') && $schema->hasTable('contacts') && $schema->hasTable('transactions')
            && $this->hasColumns($connection, 'transaction_payments', ['id'])) {
            $this->replaceTrigger($connection, 'sau_transaction_payments_ai', $this->triggerPayments($connection, 'INSERT'));
            $this->replaceTrigger($connection, 'sau_transaction_payments_au', $this->triggerPayments($connection, 'UPDATE'));
            $this->replaceTrigger($connection, 'sau_transaction_payments_ad', $this->triggerPayments($connection, 'DELETE'));
        }

        if ($schema->hasTable('account_transactions') && $schema->hasTable('transactions')
            && $schema->hasTable('transaction_payments') && $schema->hasTable('contacts')
            && $this->hasColumns($connection, 'account_transactions', ['id','account_id'])) {
            $this->replaceTrigger($connection, 'sau_account_transactions_ai', $this->triggerAccountTransactions($connection, 'INSERT'));
            $this->replaceTrigger($connection, 'sau_account_transactions_au', $this->triggerAccountTransactions($connection, 'UPDATE'));
            $this->replaceTrigger($connection, 'sau_account_transactions_ad', $this->triggerAccountTransactions($connection, 'DELETE'));
        }

        if ($schema->hasTable('contact_ledgers') && $schema->hasTable('contacts') && $schema->hasTable('transactions')
            && $schema->hasTable('transaction_payments')
            && $this->hasColumns($connection, 'contact_ledgers', ['id','contact_id'])) {
            $this->replaceTrigger($connection, 'sau_contact_ledgers_ai', $this->triggerContactLedgers($connection, 'INSERT'));
            $this->replaceTrigger($connection, 'sau_contact_ledgers_au', $this->triggerContactLedgers($connection, 'UPDATE'));
            $this->replaceTrigger($connection, 'sau_contact_ledgers_ad', $this->triggerContactLedgers($connection, 'DELETE'));
        }
    }

    public function dropTriggers($connection = null)
    {
        $connection = $connection ?: config('database.default');
        $names = [
            'sau_vld_ai','sau_vld_au','sau_vld_ad',
            'sau_transactions_ai','sau_transactions_au','sau_transactions_ad',
            'sau_purchase_lines_ai','sau_purchase_lines_au','sau_purchase_lines_ad',
            'sau_stock_adjustment_lines_ai','sau_stock_adjustment_lines_au','sau_stock_adjustment_lines_ad',
            'sau_transaction_payments_ai','sau_transaction_payments_au','sau_transaction_payments_ad',
            'sau_account_transactions_ai','sau_account_transactions_au','sau_account_transactions_ad',
            'sau_contact_ledgers_ai','sau_contact_ledgers_au','sau_contact_ledgers_ad',
        ];
        foreach ($names as $name) {
            try {
                DB::connection($connection)->unprepared('DROP TRIGGER IF EXISTS `' . $name . '`');
            } catch (Throwable $e) {
                // Continue so uninstall/repair is resilient.
            }
        }
    }

    protected function replaceTrigger($connection, $name, $sql)
    {
        DB::connection($connection)->unprepared('DROP TRIGGER IF EXISTS `' . $name . '`');
        DB::connection($connection)->unprepared($sql);
    }

    protected function hasColumns($connection, $table, array $columns): bool
    {
        $schema = Schema::connection($connection);
        foreach ($columns as $column) {
            if (!$schema->hasColumn($table, $column)) {
                return false;
            }
        }
        return true;
    }

    protected function rowExpr($connection, $table, $row, $column, $fallback = 'NULL'): string
    {
        return Schema::connection($connection)->hasColumn($table, $column)
            ? $row . '.' . $column
            : $fallback;
    }

    protected function aliasExpr($connection, $table, $alias, $column, $fallback = 'NULL'): string
    {
        return Schema::connection($connection)->hasColumn($table, $column)
            ? $alias . '.' . $column
            : $fallback;
    }

    protected function jsonObject($connection, $table, $row, array $columns): string
    {
        $schema = Schema::connection($connection);
        $pairs = [];
        foreach ($columns as $column) {
            if ($schema->hasColumn($table, $column)) {
                $pairs[] = "'" . str_replace("'", "''", $column) . "'";
                $pairs[] = $row . '.' . $column;
            }
        }
        return 'JSON_OBJECT(' . implode(',', $pairs) . ')';
    }

    protected function changedCondition($connection, $table, array $columns): string
    {
        $schema = Schema::connection($connection);
        $parts = [];
        foreach ($columns as $column) {
            if ($schema->hasColumn($table, $column)) {
                $parts[] = "NOT (OLD.{$column} <=> NEW.{$column})";
            }
        }
        return $parts ? implode(' OR ', $parts) : 'FALSE';
    }

    protected function triggerVldInsert()
    {
        return <<<'SQL'
CREATE TRIGGER `sau_vld_ai` AFTER INSERT ON `variation_location_details`
FOR EACH ROW
BEGIN
    INSERT INTO sau_stock_snapshots
        (business_id, location_id, product_id, variation_id, qty_before, qty_after, source, source_row_id, changed_at, created_at)
    SELECT p.business_id, NEW.location_id, NEW.product_id, NEW.variation_id,
           NULL, COALESCE(NEW.qty_available,0), 'variation_location_details', NEW.id, NOW(), NOW()
      FROM products p WHERE p.id = NEW.product_id LIMIT 1;
END
SQL;
    }

    protected function triggerVldUpdate()
    {
        return <<<'SQL'
CREATE TRIGGER `sau_vld_au` AFTER UPDATE ON `variation_location_details`
FOR EACH ROW
BEGIN
    IF NOT (OLD.qty_available <=> NEW.qty_available) THEN
        INSERT INTO sau_stock_snapshots
            (business_id, location_id, product_id, variation_id, qty_before, qty_after, source, source_row_id, changed_at, created_at)
        SELECT p.business_id, NEW.location_id, NEW.product_id, NEW.variation_id,
               COALESCE(OLD.qty_available,0), COALESCE(NEW.qty_available,0), 'variation_location_details', NEW.id, NOW(), NOW()
          FROM products p WHERE p.id = NEW.product_id LIMIT 1;
    END IF;
END
SQL;
    }

    protected function triggerVldDelete()
    {
        return <<<'SQL'
CREATE TRIGGER `sau_vld_ad` AFTER DELETE ON `variation_location_details`
FOR EACH ROW
BEGIN
    INSERT INTO sau_stock_snapshots
        (business_id, location_id, product_id, variation_id, qty_before, qty_after, source, source_row_id, changed_at, created_at)
    SELECT p.business_id, OLD.location_id, OLD.product_id, OLD.variation_id,
           COALESCE(OLD.qty_available,0), 0, 'variation_location_details_delete', OLD.id, NOW(), NOW()
      FROM products p WHERE p.id = OLD.product_id LIMIT 1;
END
SQL;
    }

    protected function triggerTransactions($connection, $operation)
    {
        $name = $operation === 'INSERT' ? 'sau_transactions_ai' : ($operation === 'UPDATE' ? 'sau_transactions_au' : 'sau_transactions_ad');
        $row = $operation === 'DELETE' ? 'OLD' : 'NEW';
        $event = strtolower($operation);
        $business = $this->rowExpr($connection, 'transactions', $row, 'business_id');
        $location = $this->rowExpr($connection, 'transactions', $row, 'location_id');
        $store = $this->rowExpr($connection, 'transactions', $row, 'store_id');
        $contact = $this->rowExpr($connection, 'transactions', $row, 'contact_id');
        $actor = $this->rowExpr($connection, 'transactions', $row, 'created_by');
        $oldData = $operation === 'INSERT' ? 'NULL' : $this->jsonObject($connection, 'transactions', 'OLD', [
            'type','status','transaction_date','contact_id','location_id','store_id','ref_no','purchase_entry_no',
            'total_before_tax','tax_amount','discount_amount','final_total','payment_status','deleted_at','new_deleted_at'
        ]);
        $newData = $operation === 'DELETE' ? 'NULL' : $this->jsonObject($connection, 'transactions', 'NEW', [
            'type','status','transaction_date','contact_id','location_id','store_id','ref_no','purchase_entry_no',
            'total_before_tax','tax_amount','discount_amount','final_total','payment_status','deleted_at','new_deleted_at'
        ]);

        $body = '';
        if ($operation === 'UPDATE') {
            $changed = $this->changedCondition($connection, 'transactions', [
                'type','status','transaction_date','contact_id','location_id','store_id','total_before_tax','tax_amount',
                'discount_amount','final_total','payment_status','deleted_at','new_deleted_at','ref_no','purchase_entry_no'
            ]);
            $body .= "    IF (NEW.type IN ('purchase','purchase_return','stock_adjustment') OR OLD.type IN ('purchase','purchase_return','stock_adjustment'))\n";
            $body .= "       AND ({$changed}) THEN\n";
            $body .= "        INSERT INTO sau_change_events\n";
            $body .= "        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)\n";
            $body .= "        VALUES ({$business}, {$location}, {$store}, 'transactions', NEW.id, 'update', NEW.id, {$contact}, {$actor}, {$oldData}, {$newData}, NOW(), NOW());\n";
            $body .= "    END IF;\n";
        } else {
            $body .= "    IF {$row}.type IN ('purchase','purchase_return','stock_adjustment') THEN\n";
            $body .= "        INSERT INTO sau_change_events\n";
            $body .= "        (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)\n";
            $body .= "        VALUES ({$business}, {$location}, {$store}, 'transactions', {$row}.id, '{$event}', {$row}.id, {$contact}, {$actor}, {$oldData}, {$newData}, NOW(), NOW());\n";
            $body .= "    END IF;\n";
        }

        return "CREATE TRIGGER `{$name}` AFTER {$operation} ON `transactions`\nFOR EACH ROW\nBEGIN\n{$body}END";
    }

    protected function triggerPurchaseLines($connection, $operation)
    {
        $name = $operation === 'INSERT' ? 'sau_purchase_lines_ai' : ($operation === 'UPDATE' ? 'sau_purchase_lines_au' : 'sau_purchase_lines_ad');
        $row = $operation === 'DELETE' ? 'OLD' : 'NEW';
        $event = strtolower($operation);
        $tBusiness = $this->aliasExpr($connection, 'transactions', 't', 'business_id');
        $tLocation = $this->aliasExpr($connection, 'transactions', 't', 'location_id');
        $tStore = $this->aliasExpr($connection, 'transactions', 't', 'store_id');
        $tContact = $this->aliasExpr($connection, 'transactions', 't', 'contact_id');
        $tActor = $this->aliasExpr($connection, 'transactions', 't', 'created_by');
        $oldData = $operation === 'INSERT' ? 'NULL' : $this->jsonObject($connection, 'purchase_lines', 'OLD', [
            'quantity','purchase_price','purchase_price_inc_tax','discount_amount','discount_percent','item_tax','tax_id',
            'quantity_returned','deleted_at','new_deleted_at'
        ]);
        $newData = $operation === 'DELETE' ? 'NULL' : $this->jsonObject($connection, 'purchase_lines', 'NEW', [
            'quantity','purchase_price','purchase_price_inc_tax','discount_amount','discount_percent','item_tax','tax_id',
            'quantity_returned','deleted_at','new_deleted_at'
        ]);
        $prefix = '';
        $suffix = '';
        if ($operation === 'UPDATE') {
            $changed = $this->changedCondition($connection, 'purchase_lines', [
                'quantity','purchase_price','purchase_price_inc_tax','discount_amount','discount_percent','item_tax','tax_id',
                'quantity_returned','deleted_at','new_deleted_at','product_id','variation_id'
            ]);
            $prefix = "    IF {$changed} THEN\n";
            $suffix = "    END IF;\n";
        }

        return "CREATE TRIGGER `{$name}` AFTER {$operation} ON `purchase_lines`\nFOR EACH ROW\nBEGIN\n" .
            $prefix .
            "    INSERT INTO sau_change_events\n" .
            "    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, contact_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)\n" .
            "    SELECT {$tBusiness}, {$tLocation}, {$tStore}, 'purchase_lines', {$row}.id, '{$event}', t.id, {$tContact}, {$row}.product_id, {$row}.variation_id, {$tActor}, {$oldData}, {$newData}, NOW(), NOW()\n" .
            "      FROM transactions t\n" .
            "     WHERE t.id = {$row}.transaction_id AND t.type IN ('purchase','purchase_return','stock_adjustment')\n" .
            "     LIMIT 1;\n" .
            $suffix .
            "END";
    }

    protected function triggerStockAdjustmentLines($connection, $operation)
    {
        $name = $operation === 'INSERT' ? 'sau_stock_adjustment_lines_ai' : ($operation === 'UPDATE' ? 'sau_stock_adjustment_lines_au' : 'sau_stock_adjustment_lines_ad');
        $row = $operation === 'DELETE' ? 'OLD' : 'NEW';
        $event = strtolower($operation);
        $oldData = $operation === 'INSERT' ? 'NULL' : $this->jsonObject($connection, 'stock_adjustment_lines', 'OLD', [
            'quantity','unit_price','type','stock_adjustment_type'
        ]);
        $newData = $operation === 'DELETE' ? 'NULL' : $this->jsonObject($connection, 'stock_adjustment_lines', 'NEW', [
            'quantity','unit_price','type','stock_adjustment_type'
        ]);
        $prefix = '';
        $suffix = '';
        if ($operation === 'UPDATE') {
            $changed = $this->changedCondition($connection, 'stock_adjustment_lines', [
                'quantity','unit_price','type','stock_adjustment_type','product_id','variation_id'
            ]);
            $prefix = "    IF {$changed} THEN\n";
            $suffix = "    END IF;\n";
        }
        $tBusiness = $this->aliasExpr($connection, 'transactions', 't', 'business_id');
        $tLocation = $this->aliasExpr($connection, 'transactions', 't', 'location_id');
        $tStore = $this->aliasExpr($connection, 'transactions', 't', 'store_id');
        $tActor = $this->aliasExpr($connection, 'transactions', 't', 'created_by');

        return "CREATE TRIGGER `{$name}` AFTER {$operation} ON `stock_adjustment_lines`\nFOR EACH ROW\nBEGIN\n" .
            $prefix .
            "    INSERT INTO sau_change_events\n" .
            "    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, product_id, variation_id, actor_user_id, old_data, new_data, occurred_at, created_at)\n" .
            "    SELECT {$tBusiness}, {$tLocation}, {$tStore}, 'stock_adjustment_lines', {$row}.id, '{$event}', t.id, {$row}.product_id, {$row}.variation_id, {$tActor}, {$oldData}, {$newData}, NOW(), NOW()\n" .
            "      FROM transactions t WHERE t.id={$row}.transaction_id AND t.type='stock_adjustment' LIMIT 1;\n" .
            $suffix .
            "END";
    }

    protected function triggerPayments($connection, $operation)
    {
        $name = $operation === 'INSERT' ? 'sau_transaction_payments_ai' : ($operation === 'UPDATE' ? 'sau_transaction_payments_au' : 'sau_transaction_payments_ad');
        $row = $operation === 'DELETE' ? 'OLD' : 'NEW';
        $event = strtolower($operation);
        $rowBusiness = $this->rowExpr($connection, 'transaction_payments', $row, 'business_id');
        $rowTx = $this->rowExpr($connection, 'transaction_payments', $row, 'transaction_id');
        $rowPaymentFor = $this->rowExpr($connection, 'transaction_payments', $row, 'payment_for');
        $rowAccount = $this->rowExpr($connection, 'transaction_payments', $row, 'account_id');
        $rowActor = $this->rowExpr($connection, 'transaction_payments', $row, 'created_by');
        $tBusiness = $this->aliasExpr($connection, 'transactions', 't', 'business_id');
        $tLocation = $this->aliasExpr($connection, 'transactions', 't', 'location_id');
        $tStore = $this->aliasExpr($connection, 'transactions', 't', 'store_id');
        $tContact = $this->aliasExpr($connection, 'transactions', 't', 'contact_id');
        $cBusiness = $this->aliasExpr($connection, 'contacts', 'c', 'business_id');
        $oldData = $operation === 'INSERT' ? 'NULL' : $this->jsonObject($connection, 'transaction_payments', 'OLD', [
            'transaction_id','payment_for','amount','method','paid_on','account_id','payment_ref_no','reference_no','deleted_at'
        ]);
        $newData = $operation === 'DELETE' ? 'NULL' : $this->jsonObject($connection, 'transaction_payments', 'NEW', [
            'transaction_id','payment_for','amount','method','paid_on','account_id','payment_ref_no','reference_no','deleted_at'
        ]);
        $prefix = '';
        $suffix = '';
        if ($operation === 'UPDATE') {
            $changed = $this->changedCondition($connection, 'transaction_payments', [
                'transaction_id','payment_for','amount','method','paid_on','account_id','payment_ref_no','reference_no','deleted_at'
            ]);
            $prefix = "    IF {$changed} THEN\n";
            $suffix = "    END IF;\n";
        }

        return "CREATE TRIGGER `{$name}` AFTER {$operation} ON `transaction_payments`\nFOR EACH ROW\nBEGIN\n" .
            $prefix .
            "    INSERT INTO sau_change_events\n" .
            "    (business_id, location_id, store_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)\n" .
            "    SELECT COALESCE({$rowBusiness},{$tBusiness},{$cBusiness}), {$tLocation}, {$tStore}, 'transaction_payments', {$row}.id, '{$event}', {$rowTx}, {$row}.id, c.id, {$rowAccount}, {$rowActor}, {$oldData}, {$newData}, NOW(), NOW()\n" .
            "      FROM contacts c\n" .
            "      LEFT JOIN transactions t ON t.id = {$rowTx}\n" .
            "     WHERE c.id = COALESCE({$rowPaymentFor},{$tContact}) AND c.type IN ('supplier','both')\n" .
            "     LIMIT 1;\n" .
            $suffix .
            "END";
    }

    protected function triggerAccountTransactions($connection, $operation)
    {
        $name = $operation === 'INSERT' ? 'sau_account_transactions_ai' : ($operation === 'UPDATE' ? 'sau_account_transactions_au' : 'sau_account_transactions_ad');
        $row = $operation === 'DELETE' ? 'OLD' : 'NEW';
        $event = strtolower($operation);

        $rowBusiness = $this->rowExpr($connection, 'account_transactions', $row, 'business_id');
        $rowLocation = $this->rowExpr($connection, 'account_transactions', $row, 'location_id');
        $rowTx = $this->rowExpr($connection, 'account_transactions', $row, 'transaction_id');
        $rowPayment = $this->rowExpr($connection, 'account_transactions', $row, 'transaction_payment_id');
        $rowAccount = $this->rowExpr($connection, 'account_transactions', $row, 'account_id');
        $rowActor = $this->rowExpr($connection, 'account_transactions', $row, 'created_by');

        $txBusiness = $this->aliasExpr($connection, 'transactions', 'tx', 'business_id');
        $txLocation = $this->aliasExpr($connection, 'transactions', 'tx', 'location_id');
        $txpBusiness = $this->aliasExpr($connection, 'transactions', 'txp', 'business_id');
        $txpLocation = $this->aliasExpr($connection, 'transactions', 'txp', 'location_id');
        $cBusiness = $this->aliasExpr($connection, 'contacts', 'c', 'business_id');
        $tpTx = $this->aliasExpr($connection, 'transaction_payments', 'tp', 'transaction_id');
        $tpPaymentFor = $this->aliasExpr($connection, 'transaction_payments', 'tp', 'payment_for');
        $txpContact = $this->aliasExpr($connection, 'transactions', 'txp', 'contact_id');

        $oldData = $operation === 'INSERT' ? 'NULL' : $this->jsonObject($connection, 'account_transactions', 'OLD', [
            'account_id','type','amount','operation_date','transaction_id','transaction_payment_id','reff_no','deleted_at','new_deleted_at','reversed'
        ]);
        $newData = $operation === 'DELETE' ? 'NULL' : $this->jsonObject($connection, 'account_transactions', 'NEW', [
            'account_id','type','amount','operation_date','transaction_id','transaction_payment_id','reff_no','deleted_at','new_deleted_at','reversed'
        ]);

        $prefix = '';
        $suffix = '';
        if ($operation === 'UPDATE') {
            $changed = $this->changedCondition($connection, 'account_transactions', [
                'account_id','type','amount','operation_date','transaction_id','transaction_payment_id','reff_no','deleted_at','new_deleted_at','reversed','business_id','location_id'
            ]);
            $prefix = "    IF {$changed} THEN\n";
            $suffix = "    END IF;\n";
        }

        /*
         * Older tenant databases do not always have account_transactions.location_id
         * (and some also pre-date new_deleted_at/reversed). Never reference a column
         * that is absent. Business/location are derived from the linked purchase or
         * supplier-payment transaction when the row itself does not carry them.
         */
        return "CREATE TRIGGER `{$name}` AFTER {$operation} ON `account_transactions`\nFOR EACH ROW\nBEGIN\n" .
            $prefix .
            "    INSERT INTO sau_change_events\n" .
            "    (business_id, location_id, source_table, source_id, event_type, transaction_id, payment_id, account_id, actor_user_id, old_data, new_data, occurred_at, created_at)\n" .
            "    SELECT COALESCE({$rowBusiness},{$txBusiness},{$txpBusiness},{$cBusiness}), COALESCE({$rowLocation},{$txLocation},{$txpLocation}),\n" .
            "           'account_transactions', {$row}.id, '{$event}', {$rowTx}, {$rowPayment}, {$rowAccount}, {$rowActor}, {$oldData}, {$newData}, NOW(), NOW()\n" .
            "      FROM (SELECT 1 AS sau_seed) s\n" .
            "      LEFT JOIN transactions tx ON tx.id = {$rowTx}\n" .
            "      LEFT JOIN transaction_payments tp ON tp.id = {$rowPayment}\n" .
            "      LEFT JOIN transactions txp ON txp.id = {$tpTx}\n" .
            "      LEFT JOIN contacts c ON c.id = COALESCE({$tpPaymentFor},{$txpContact})\n" .
            "     WHERE (tx.type IN ('purchase','purchase_return','stock_adjustment') OR c.type IN ('supplier','both'))\n" .
            "     LIMIT 1;\n" .
            $suffix .
            "END";
    }

    protected function triggerContactLedgers($connection, $operation)
    {
        $name = $operation === 'INSERT' ? 'sau_contact_ledgers_ai' : ($operation === 'UPDATE' ? 'sau_contact_ledgers_au' : 'sau_contact_ledgers_ad');
        $row = $operation === 'DELETE' ? 'OLD' : 'NEW';
        $event = strtolower($operation);
        $rowBusiness = $this->rowExpr($connection, 'contact_ledgers', $row, 'business_id');
        $rowContact = $this->rowExpr($connection, 'contact_ledgers', $row, 'contact_id');
        $rowTx = $this->rowExpr($connection, 'contact_ledgers', $row, 'transaction_id');
        $rowPayment = $this->rowExpr($connection, 'contact_ledgers', $row, 'transaction_payment_id');
        $rowActor = $this->rowExpr($connection, 'contact_ledgers', $row, 'created_by');
        $cBusiness = $this->aliasExpr($connection, 'contacts', 'c', 'business_id');
        $oldData = $operation === 'INSERT' ? 'NULL' : $this->jsonObject($connection, 'contact_ledgers', 'OLD', [
            'contact_id','type','amount','operation_date','transaction_id','transaction_payment_id','reff_no','deleted_at','reversed'
        ]);
        $newData = $operation === 'DELETE' ? 'NULL' : $this->jsonObject($connection, 'contact_ledgers', 'NEW', [
            'contact_id','type','amount','operation_date','transaction_id','transaction_payment_id','reff_no','deleted_at','reversed'
        ]);
        $prefix = '';
        $suffix = '';
        if ($operation === 'UPDATE') {
            $changed = $this->changedCondition($connection, 'contact_ledgers', [
                'contact_id','type','amount','operation_date','transaction_id','transaction_payment_id','reff_no','deleted_at','reversed','business_id'
            ]);
            $prefix = "    IF {$changed} THEN\n";
            $suffix = "    END IF;\n";
        }

        return "CREATE TRIGGER `{$name}` AFTER {$operation} ON `contact_ledgers`\nFOR EACH ROW\nBEGIN\n" .
            $prefix .
            "    INSERT INTO sau_change_events\n" .
            "    (business_id, source_table, source_id, event_type, transaction_id, payment_id, contact_id, actor_user_id, old_data, new_data, occurred_at, created_at)\n" .
            "    SELECT COALESCE({$rowBusiness},{$cBusiness}), 'contact_ledgers', {$row}.id, '{$event}', {$rowTx}, {$rowPayment}, {$rowContact}, {$rowActor}, {$oldData}, {$newData}, NOW(), NOW()\n" .
            "      FROM contacts c WHERE c.id={$rowContact} AND c.type IN ('supplier','both')\n" .
            "       AND (EXISTS (SELECT 1 FROM transactions tx WHERE tx.id={$rowTx} AND tx.type IN ('purchase','purchase_return','stock_adjustment'))\n" .
            "            OR EXISTS (SELECT 1 FROM transaction_payments tp WHERE tp.id={$rowPayment})) LIMIT 1;\n" .
            $suffix .
            "END";
    }
}
