<?php
use Illuminate\Database\Migrations\Migration;
use Modules\RestaurantNew\Database\Seeders\RestaurantNewPermissionSeeder;
return new class extends Migration { public function up(): void { (new RestaurantNewPermissionSeeder())->run(); } public function down(): void {} };
