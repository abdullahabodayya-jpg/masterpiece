<?php

use App\Enums\RevisionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deliverable_revisions', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('deliverable_id')->constrained('deliverables')->restrictOnDelete();
            $table->foreignUuid('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('reason', 255);
            $table->text('details')->nullable();
            $table->enum('status', array_column(RevisionStatus::cases(), 'value'))->default('requested');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliverable_revisions');
    }
};
