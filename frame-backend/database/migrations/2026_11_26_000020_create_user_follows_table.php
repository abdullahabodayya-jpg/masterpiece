<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_follows', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->foreignUuid('follower_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('following_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
            $table->timestamp('updated_at')->nullable();
            $table->primary(['follower_id', 'following_id']);
        });

        DB::statement('ALTER TABLE user_follows ADD CONSTRAINT user_follows_different_users CHECK (follower_id <> following_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_follows');
    }
};
