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
        Schema::create('user_content_types', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();

            $table->foreignUuid('content_type_id')->constrained('content_types')->restrictOnDelete();

            $table->primary(['user_id', 'content_type_id']);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_content_types');
    }
};
