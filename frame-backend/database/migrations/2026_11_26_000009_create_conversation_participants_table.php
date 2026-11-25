<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->foreignUuid('conversation_id')->constrained('conversations')->restrictOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('last_read_at')->nullable();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->primary(['conversation_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
