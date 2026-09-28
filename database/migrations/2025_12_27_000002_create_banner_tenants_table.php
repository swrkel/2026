
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
   Schema::create('banner_tenants', function (Blueprint $table) {
    $table->id();
    $table->foreignId('banner_id')->constrained()->onDelete('cascade');
    $table->string('tenant_id')->nullable();
    $table->timestamps();
    
    $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
});

    }

    public function down(): void
    {
        Schema::dropIfExists('banner_tenants');
    }
};
