<?php

use App\Enums\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_applications', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('work_item_id')->constrained('work_items')->restrictOnDelete();
            $table->foreignUuid('editor_id')->constrained('users')->restrictOnDelete();
            $table->text('cover_message');
            $table->decimal('proposed_price', 12, 2)->nullable();
            $table->integer('estimated_delivery_days')->nullable();
            $table->enum('status', array_column(ApplicationStatus::cases(), 'value'))->default('pending');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['work_item_id', 'editor_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
    }
};
