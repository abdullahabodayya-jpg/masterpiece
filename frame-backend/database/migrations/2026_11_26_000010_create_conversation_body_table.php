<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversation_body', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_id')->constrained('conversations')->restrictOnDelete();
            $table->foreignUuid('sender_id')->constrained('users')->restrictOnDelete();
            $table->text('message_body')->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('storage_key', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes();
            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversation_body');
    }
};
