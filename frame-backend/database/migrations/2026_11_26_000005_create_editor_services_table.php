<?php

use App\Enums\ServiceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_services', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('editor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('content_type_id')->nullable()->constrained('content_types')->restrictOnDelete();
            $table->string('title', 255);
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('JOD');
            $table->integer('delivery_days')->nullable();
            $table->enum('status', array_column(ServiceStatus::cases(), 'value'))->default('draft');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_services');
    }
};
