<?php

use App\Enums\VerificationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_verification_requests', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('editor_id')->constrained('users')->restrictOnDelete();
            $table->enum('status', array_column(VerificationStatus::cases(), 'value'))->default('draft');
            $table->string('document_type', 100)->nullable();
            $table->string('document_file_name', 255)->nullable();
            $table->string('document_storage_key', 512)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('decision_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_verification_requests');
    }
};
