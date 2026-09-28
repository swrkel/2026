<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(){
  if(!Schema::hasTable('auto_service_package_categories')) Schema::create('auto_service_package_categories',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('business_id')->nullable()->index();$t->unsignedBigInteger('location_id')->nullable()->index();$t->string('code')->nullable();$t->string('name');$t->boolean('is_active')->default(1);$t->timestamps();$t->softDeletes();$t->unique(['business_id','code'],'as_pkg_cat_business_code_uq');});
  if(!Schema::hasTable('auto_service_job_packages')) Schema::create('auto_service_job_packages',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('business_id')->nullable()->index();$t->unsignedBigInteger('location_id')->nullable()->index();$t->unsignedBigInteger('job_id')->index();$t->unsignedBigInteger('package_id')->index();$t->string('package_name');$t->decimal('package_price',22,4)->default(0);$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});
  if(!Schema::hasTable('auto_service_package_stock_movements')) Schema::create('auto_service_package_stock_movements',function(Blueprint $t){$t->bigIncrements('id');$t->unsignedBigInteger('business_id')->nullable()->index();$t->unsignedBigInteger('location_id')->nullable()->index();$t->unsignedBigInteger('invoice_id')->nullable()->index();$t->unsignedBigInteger('job_id')->nullable()->index();$t->unsignedBigInteger('invoice_line_id')->nullable()->index();$t->unsignedBigInteger('package_id')->nullable()->index();$t->unsignedBigInteger('product_id')->nullable()->index();$t->unsignedBigInteger('variation_id')->nullable()->index();$t->string('movement_type')->index();$t->decimal('quantity',22,4);$t->decimal('unit_price',22,4)->default(0);$t->string('reference_no')->nullable()->index();$t->unsignedBigInteger('created_by')->nullable();$t->timestamps();});

  foreach([
   'auto_service_service_packages'=>['category_id'=>'unsignedBigInteger','vehicle_brand'=>'string','vehicle_model'=>'string','estimated_minutes'=>'integer','warranty_days'=>'integer','non_stock_amount'=>'decimal','subtotal_amount'=>'decimal','package_discount'=>'decimal','package_tax'=>'decimal','selling_price'=>'decimal'],
   'auto_service_package_lines'=>['location_id'=>'unsignedBigInteger','component_type'=>'string','variation_id'=>'unsignedBigInteger','item_name'=>'string','unit_name'=>'string','discount_amount'=>'decimal','tax_amount'=>'decimal','is_optional'=>'boolean','is_stock_item'=>'boolean','sort_order'=>'integer'],
   'auto_service_job_lines'=>['component_type'=>'string','package_id'=>'unsignedBigInteger','package_line_id'=>'unsignedBigInteger','variation_id'=>'unsignedBigInteger','is_stock_item'=>'boolean'],
   'auto_service_invoice_lines'=>['component_type'=>'string','package_id'=>'unsignedBigInteger','package_line_id'=>'unsignedBigInteger','variation_id'=>'unsignedBigInteger','is_stock_item'=>'boolean'],
   'auto_service_invoices'=>['posted_at'=>'dateTime','stock_posted_at'=>'dateTime','posted_by'=>'unsignedBigInteger']
  ] as $table=>$columns){
   if(!Schema::hasTable($table)) continue;
   Schema::table($table,function(Blueprint $t) use($table,$columns){
    foreach($columns as $name=>$type){
     if(Schema::hasColumn($table,$name)) continue;
     if($type==='unsignedBigInteger') $t->unsignedBigInteger($name)->nullable()->index();
     elseif($type==='string') $t->string($name)->nullable()->index();
     elseif($type==='integer') $t->integer($name)->nullable();
     elseif($type==='boolean') $t->boolean($name)->default(0)->index();
     elseif($type==='decimal') $t->decimal($name,22,4)->default(0);
     elseif($type==='dateTime') $t->dateTime($name)->nullable()->index();
    }
   });
  }
 }
 public function down(){}
};
