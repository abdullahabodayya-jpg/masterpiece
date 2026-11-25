<?php

use App\Enums\RefundStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('payment_id')->constrained('payments')->restrictOnDelete();
            $table->foreignUuid('requested_by_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('JOD');
            $table->text('reason')->nullable();
            $table->enum('status', array_column(RefundStatus::cases(), 'value'))->default('pending');
            $table->string('provider_refund_id', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->index('status');
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER refunds_retain_history
            BEFORE DELETE ON refunds
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Financial records must be retained'
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
