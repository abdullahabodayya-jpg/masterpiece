<?php

use App\Enums\DeliverableStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverables', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('work_item_id')->constrained('work_items')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->integer('sequence_number')->default(1);
            $table->enum('status', array_column(DeliverableStatus::cases(), 'value'))->default('pending');
            $table->timestamp('due_at')->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('storage_key', 512)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['work_item_id', 'sequence_number']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverables');
    }
};
