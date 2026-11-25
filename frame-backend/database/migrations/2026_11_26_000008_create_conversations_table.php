<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('work_item_id')->nullable()->constrained('work_items')->restrictOnDelete();
            $table->string('title', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
