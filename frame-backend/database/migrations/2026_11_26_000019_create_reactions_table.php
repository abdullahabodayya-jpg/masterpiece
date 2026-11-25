<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reactions', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('post_id')->nullable()->constrained('posts')->restrictOnDelete();
            $table->foreignUuid('comment_id')->nullable()->constrained('comments')->restrictOnDelete();
            $table->string('reaction_type', 50);
            $table->timestamp('created_at')->useCurrent();
            $table->softDeletes();
            $table->unique(['post_id', 'user_id']);
            $table->unique(['comment_id', 'user_id']);
        });

        DB::statement('ALTER TABLE reactions ADD CONSTRAINT reactions_one_target CHECK ((post_id IS NULL) <> (comment_id IS NULL))');
    }

    public function down(): void
    {
        Schema::dropIfExists('reactions');
    }
};
