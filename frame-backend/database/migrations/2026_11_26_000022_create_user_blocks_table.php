<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_blocks', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->foreignUuid('blocker_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('blockee_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
            $table->timestamp('updated_at')->nullable();
            $table->primary(['blocker_id', 'blockee_id']);
        });

        DB::statement('ALTER TABLE user_blocks ADD CONSTRAINT user_blocks_different_users CHECK (blocker_id <> blockee_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_blocks');
    }
};
