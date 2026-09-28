<?php

namespace Modules\CommunicationHub\Support;

use Illuminate\Database\Schema\Blueprint;

/**
 * Small, tenant-safe schema guard for the two configuration tables that are
 * required to open/save Communication Hub Settings and SMS Packages.
 *
 * It is intentionally limited to module-owned tables and never drops data.
 */
class CommunicationHubSchemaGuard
{
    public static function ensureSettingsTable(): bool
    {
        try {
            $schema = TenantConnection::schema();

            if (! $schema->hasTable('communication_hub_settings')) {
                $schema->create('communication_hub_settings', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->unsignedBigInteger('business_id')->nullable()->index();
                    $table->string('group')->nullable()->index();
                    $table->string('key')->index();
                    $table->longText('value')->nullable();
                    $table->json('meta')->nullable();
                    $table->timestamps();
                });
            } else {
                static::addSettingsColumns($schema);
            }

            return $schema->hasTable('communication_hub_settings');
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public static function ensureSmsPackagesTable(): bool
    {
        try {
            $schema = TenantConnection::schema();

            if (! $schema->hasTable('communication_hub_sms_packages')) {
                $schema->create('communication_hub_sms_packages', function (Blueprint $table) {
                    $table->bigIncrements('id');
                    $table->unsignedBigInteger('business_id')->nullable()->index();
                    $table->string('code', 100)->nullable()->index();
                    $table->string('name', 191);
                    $table->unsignedInteger('credits')->default(0);
                    $table->decimal('cost_price', 18, 4)->default(0);
                    $table->decimal('selling_price', 18, 4)->default(0);
                    $table->decimal('profit_amount', 18, 4)->default(0);
                    $table->unsignedInteger('validity_days')->default(0);
                    $table->text('description')->nullable();
                    $table->boolean('is_active')->default(true)->index();
                    $table->unsignedBigInteger('created_by')->nullable();
                    $table->timestamps();
                });
            } else {
                static::addSmsPackageColumns($schema);
            }

            return $schema->hasTable('communication_hub_sms_packages');
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    protected static function addSettingsColumns($schema): void
    {
        $table = 'communication_hub_settings';
        $missing = [];
        foreach (['business_id', 'group', 'key', 'value', 'meta', 'created_at', 'updated_at'] as $column) {
            if (! $schema->hasColumn($table, $column)) {
                $missing[] = $column;
            }
        }

        if (! $missing) {
            return;
        }

        $schema->table($table, function (Blueprint $blueprint) use ($missing) {
            foreach ($missing as $column) {
                switch ($column) {
                    case 'business_id': $blueprint->unsignedBigInteger('business_id')->nullable()->index(); break;
                    case 'group': $blueprint->string('group')->nullable()->index(); break;
                    case 'key': $blueprint->string('key')->nullable()->index(); break;
                    case 'value': $blueprint->longText('value')->nullable(); break;
                    case 'meta': $blueprint->json('meta')->nullable(); break;
                    case 'created_at': $blueprint->timestamp('created_at')->nullable(); break;
                    case 'updated_at': $blueprint->timestamp('updated_at')->nullable(); break;
                }
            }
        });
    }

    protected static function addSmsPackageColumns($schema): void
    {
        $table = 'communication_hub_sms_packages';
        $missing = [];
        foreach ([
            'business_id', 'code', 'name', 'credits', 'cost_price', 'selling_price',
            'profit_amount', 'validity_days', 'description', 'is_active', 'created_by',
            'created_at', 'updated_at'
        ] as $column) {
            if (! $schema->hasColumn($table, $column)) {
                $missing[] = $column;
            }
        }

        if (! $missing) {
            return;
        }

        $schema->table($table, function (Blueprint $blueprint) use ($missing) {
            foreach ($missing as $column) {
                switch ($column) {
                    case 'business_id': $blueprint->unsignedBigInteger('business_id')->nullable()->index(); break;
                    case 'code': $blueprint->string('code', 100)->nullable()->index(); break;
                    case 'name': $blueprint->string('name', 191)->nullable(); break;
                    case 'credits': $blueprint->unsignedInteger('credits')->default(0); break;
                    case 'cost_price': $blueprint->decimal('cost_price', 18, 4)->default(0); break;
                    case 'selling_price': $blueprint->decimal('selling_price', 18, 4)->default(0); break;
                    case 'profit_amount': $blueprint->decimal('profit_amount', 18, 4)->default(0); break;
                    case 'validity_days': $blueprint->unsignedInteger('validity_days')->default(0); break;
                    case 'description': $blueprint->text('description')->nullable(); break;
                    case 'is_active': $blueprint->boolean('is_active')->default(true)->index(); break;
                    case 'created_by': $blueprint->unsignedBigInteger('created_by')->nullable(); break;
                    case 'created_at': $blueprint->timestamp('created_at')->nullable(); break;
                    case 'updated_at': $blueprint->timestamp('updated_at')->nullable(); break;
                }
            }
        });
    }
}
