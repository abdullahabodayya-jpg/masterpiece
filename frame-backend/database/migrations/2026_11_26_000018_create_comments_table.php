<?php

use App\Enums\CommentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('post_id')->constrained('posts')->restrictOnDelete();
            $table->foreignUuid('author_id')->constrained('users')->restrictOnDelete();
            $table->text('body');
            $table->enum('status', array_column(CommentStatus::cases(), 'value'))->default('published');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
