<?php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('work_item_id')->constrained('work_items')->restrictOnDelete();
            $table->foreignUuid('creator_id')->constrained('users')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('JOD');
            $table->decimal('platform_fee', 12, 2)->default(0);
            $table->decimal('editor_net_amount', 12, 2);
            $table->enum('status', array_column(PaymentStatus::cases(), 'value'))->default('pending');
            $table->string('provider_name', 100)->nullable();
            $table->string('provider_payment_id', 255)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('paid_at')->nullable();
            $table->unique('provider_payment_id');
            $table->index('status');
            $table->unique(['id', 'work_item_id'], 'payments_id_work_unique');
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER payments_retain_history
            BEFORE DELETE ON payments
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Financial records must be retained'
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
