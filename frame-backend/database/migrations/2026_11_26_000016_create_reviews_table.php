<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('work_item_id')->constrained('work_items')->restrictOnDelete();
            $table->foreignUuid('reviewer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('reviewed_user_id')->constrained('users')->restrictOnDelete();
            $table->integer('rating');
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
            $table->unique(['work_item_id', 'reviewer_user_id', 'reviewed_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
