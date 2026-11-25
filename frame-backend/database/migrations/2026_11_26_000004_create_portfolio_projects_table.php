<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_projects', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('editor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('content_type_id')->constrained('content_types')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('sample_type', 50)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('storage_key', 512)->nullable();
            $table->string('external_url', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_projects');
    }
};
