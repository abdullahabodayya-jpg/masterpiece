<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_links', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('platform_name', 100)->nullable();
            $table->string('title', 100)->nullable();
            $table->string('url', 512);
            $table->string('handle', 100)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_links');
    }
};
