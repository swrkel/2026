<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            if (!Schema::hasColumn('products', 'preparation_time_in_minutes')) {
                Schema::table('products', function (Blueprint $table): void {
                    $table->unsignedInteger('preparation_time_in_minutes')->nullable();
                });
            }
            if (!Schema::hasColumn('products', 'sale_tax')) {
                Schema::table('products', function (Blueprint $table): void {
                    $table->integer('sale_tax')->nullable();
                });
            }
            if (!Schema::hasColumn('products', 'stock_type')) {
                Schema::table('products', function (Blueprint $table): void {
                    $table->string('stock_type')->nullable();
                });
            }
            if (!Schema::hasColumn('products', 'date')) {
                Schema::table('products', function (Blueprint $table): void {
                    $table->date('date')->nullable();
                });
            }
            if (!Schema::hasColumn('products', 'vat_claimed')) {
                Schema::table('products', function (Blueprint $table): void {
                    $table->boolean('vat_claimed')->default(false);
                });
            }
        }

        if (Schema::hasTable('categories')) {
            $definitions = [
                'vat_exempted' => fn (Blueprint $table) => $table->string('vat_exempted', 10)->default('No'),
                'add_related_account' => fn (Blueprint $table) => $table->string('add_related_account', 30)->nullable(),
                'cogs_account_id' => fn (Blueprint $table) => $table->unsignedInteger('cogs_account_id')->nullable()->index(),
                'sales_income_account_id' => fn (Blueprint $table) => $table->unsignedInteger('sales_income_account_id')->nullable()->index(),
                'weight_excess_loss_applicable' => fn (Blueprint $table) => $table->boolean('weight_excess_loss_applicable')->default(false),
                'vat_based_on' => fn (Blueprint $table) => $table->string('vat_based_on', 20)->default('sale_price'),
                'apply_vat_on' => fn (Blueprint $table) => $table->string('apply_vat_on')->default('on_product_sub_category_settings'),
                'vat_not_applicable' => fn (Blueprint $table) => $table->integer('vat_not_applicable')->default(0),
            ];

            foreach ($definitions as $column => $definition) {
                if (!Schema::hasColumn('categories', $column)) {
                    Schema::table('categories', function (Blueprint $table) use ($definition): void {
                        $definition($table);
                    });
                }
            }
        }

        if (!Schema::hasTable('products_new_category_profiles')) {
            Schema::create('products_new_category_profiles', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('category_id')->unique();
                $table->boolean('category_code_is_hsn')->default(false);
                $table->json('settings')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('products_new_category_profiles');
    }
};
