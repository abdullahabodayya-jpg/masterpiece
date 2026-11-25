<?php

use App\Enums\PostStatus;
use App\Enums\PostVisibility;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body')->nullable();
            $table->enum('visibility', array_column(PostVisibility::cases(), 'value'))->default('public');
            $table->enum('status', array_column(PostStatus::cases(), 'value'))->default('published');
            $table->string('media_file_name', 255)->nullable();
            $table->string('media_storage_key', 512)->nullable();
            $table->string('media_type', 50)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
