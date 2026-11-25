<?php

use App\Enums\PayoutStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->uuid('id')->primary();
            $table->foreignUuid('work_item_id')->constrained('work_items')->restrictOnDelete();
            $table->foreignUuid('editor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('payment_id')->constrained('payments')->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('JOD');
            $table->enum('status', array_column(PayoutStatus::cases(), 'value'))->default('pending');
            $table->string('provider_payout_id', 255)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('paid_at')->nullable();
            $table->unique('work_item_id');
            $table->index('status');
            $table->foreign(['payment_id', 'work_item_id'], 'payouts_payment_work_fk')
                ->references(['id', 'work_item_id'])->on('payments')->restrictOnDelete();
        });

        DB::unprepared(<<<'SQL'
            CREATE TRIGGER payouts_retain_history
            BEFORE DELETE ON payouts
            FOR EACH ROW
            SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Financial records must be retained'
            SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
