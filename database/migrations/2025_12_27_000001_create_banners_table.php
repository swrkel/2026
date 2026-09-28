

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
  Schema::create('banners', function (Blueprint $table) {
    $table->id();
    $table->string('title')->nullable();
    $table->string('image_path', 500);
    $table->string('link_url', 500)->nullable();
    $table->string('storage_disk', 500)->nullable();
    $table->string('banner_code', 500)->nullable();
    $table->integer('display_duration')->default(5);
    $table->boolean('is_active')->default(true);
    $table->unsignedBigInteger('created_by')->nullable();
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
