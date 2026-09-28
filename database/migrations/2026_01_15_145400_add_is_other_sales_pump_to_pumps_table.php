<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pumps', function (Blueprint $table) {
            $table->boolean('is_other_sales_pump')->default(false)->after('business_id');
        });

        // Add "Other Sales" pump for each business
        $businesses = \App\Business::all();
        foreach ($businesses as $business) {
            $exists = \DB::table('pumps')
                ->where('business_id', $business->id)
                ->where('is_other_sales_pump', true)
                ->exists();

            if (!$exists) {
                // Get the first location for this business to associate with the pump
                $location = \App\BusinessLocation::where('business_id', $business->id)->first();
                
                \DB::table('pumps')->insert([
                    'business_id' => $business->id,
                    'location_id' => $location ? $location->id : null,
                    'pump_name' => 'Other Sales',
                    'pump_no' => 'Other Sales',
                    'is_other_sales_pump' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pumps', function (Blueprint $table) {
            $table->dropColumn('is_other_sales_pump');
        });
    }
};
