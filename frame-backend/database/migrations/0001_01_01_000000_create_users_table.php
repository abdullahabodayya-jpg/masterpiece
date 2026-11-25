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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password_hash');
            $table->enum('role', ['creator', 'editor', 'admin', 'support']);
            $table->enum('status', ['pending_verification', 'active', 'restricted', 'suspended', 'closed'])->default('pending_verification');
            $table->string('display_name')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_image_key', 512)->nullable();
            $table->string('cover_image_key', 512)->nullable();
            $table->string('creator_niche', 100)->nullable();
            $table->unsignedInteger('audience_size')->nullable();
            $table->timestamp('audience_size_updated_at')->nullable();
            $table->enum('experience_level', ['junior', 'intermediate', 'senior', 'expert'])->nullable();
            $table->unsignedSmallInteger('years_of_experience')->nullable();
            $table->string('professional_title')->nullable();
            $table->boolean('is_open_to_work')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();

            $table->index('role');
            $table->index('status');    
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
