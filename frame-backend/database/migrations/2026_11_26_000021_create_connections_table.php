<?php

use App\Enums\ConnectionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('recipient_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', array_column(ConnectionStatus::cases(), 'value'))->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->unique(['requester_id', 'recipient_id']);
        });

        DB::statement('ALTER TABLE connections ADD CONSTRAINT connections_different_users CHECK (requester_id <> recipient_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('connections');
    }
};
