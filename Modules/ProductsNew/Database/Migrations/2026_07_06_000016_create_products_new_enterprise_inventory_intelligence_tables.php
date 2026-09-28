<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
class CreateProductsNewEnterpriseInventoryIntelligenceTables extends Migration
{
 public function up(): void { /* Raw SQL equivalent is provided in Database/SQL for multi-tenant rollout. */ }
 public function down(): void { Schema::dropIfExists('products_new_stock_intelligence_snapshots'); Schema::dropIfExists('products_new_product_classifications'); Schema::dropIfExists('products_new_cost_snapshots'); Schema::dropIfExists('products_new_reorder_proposals'); Schema::dropIfExists('products_new_inventory_planning_profiles'); }
}
