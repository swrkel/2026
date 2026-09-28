<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('transaction_sell_lines', function (Blueprint $table) {
			$table->increments('id');
			$table->unsignedInteger('transaction_id');
			$table->unsignedInteger('product_id');
			$table->unsignedInteger('variation_id');
			
			$table->decimal('quantity', 22, 5)->default(0.00000);
			$table->decimal('mfg_waste_percent', 20, 6)->default(0.000000);
			$table->decimal('quantity_returned', 20, 6)->default(0.000000);
			$table->decimal('unit_price_before_discount', 22, 6)->default(0.000000);
			$table->decimal('unit_price', 22, 6)->nullable()->comment('Sell price excluding tax');
			$table->enum('line_discount_type', ['fixed', 'percentage'])->nullable();
			$table->decimal('line_discount_amount', 22, 6)->default(0.000000);
			$table->decimal('unit_price_inc_tax', 22, 6)->nullable()->comment('Sell price including tax');
			$table->decimal('item_tax', 22, 6)->comment('Tax for one quantity');
			$table->unsignedInteger('tax_id')->nullable();
			$table->unsignedInteger('discount_id')->nullable();
			$table->unsignedInteger('lot_no_line_id')->nullable();
			$table->text('sell_line_note')->nullable();
			$table->unsignedInteger('res_service_staff_id')->nullable();
			$table->string('res_line_order_status', 191)->nullable();
			$table->unsignedInteger('parent_sell_line_id')->nullable();
			$table->string('children_type', 191)->default('')->comment('Type of children for the parent, like modifier or combo');
			$table->unsignedInteger('sub_unit_id')->nullable();
			$table->decimal('weight_excess', 15, 6)->nullable();
			$table->decimal('weight_loss', 15, 6)->nullable();
			$table->decimal('last_purchased_price', 15, 6)->nullable()->comment('used for price later');
			
			// balance_quantity as text but nullable (cannot have string default in Laravel)
			$table->text('balance_quantity')->nullable();
			
			$table->unsignedInteger('so_line_id');
			$table->decimal('so_quantity_invoiced', 22, 4)->default(0.0000);
			$table->timestamps();
			
			// Indexes
			$table->index('transaction_id');
			$table->index('product_id');
			$table->index('variation_id');
			$table->index('tax_id');
			$table->index('discount_id');
			$table->index('lot_no_line_id');
			$table->index('res_service_staff_id');
			$table->index('parent_sell_line_id');
			$table->index('sub_unit_id');
			$table->index('so_line_id');
		});
	}
	
	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::dropIfExists('transaction_sell_lines_purchase_lines');
	}
};
