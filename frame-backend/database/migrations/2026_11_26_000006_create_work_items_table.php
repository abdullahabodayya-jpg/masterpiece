<?php

use App\Enums\WorkStatus;
use App\Enums\WorkType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_items', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('creator_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('editor_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignUuid('editor_service_id')->nullable()->constrained('editor_services')->restrictOnDelete();
            $table->enum('type', array_column(WorkType::cases(), 'value'))->default('job');
            $table->string('title', 255);
            $table->text('description');
            $table->foreignUuid('content_type_id')->constrained('content_types')->restrictOnDelete();
            $table->decimal('budget_min', 12, 2)->nullable();
            $table->decimal('budget_max', 12, 2)->nullable();
            $table->decimal('agreed_price', 12, 2)->nullable();
            $table->string('currency', 3)->default('JOD');
            $table->foreignUuid('editing_tool_id')->nullable()->constrained('editing_tools')->restrictOnDelete();
            $table->integer('quantity')->nullable();
            $table->timestamp('deadline')->nullable();
            $table->enum('status', array_column(WorkStatus::cases(), 'value'))->default('draft');
            $table->timestamp('agreed_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->index(['type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_items');
    }
};
