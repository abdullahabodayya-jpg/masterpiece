<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('editor_tools', function (Blueprint $table) {
            $table->engine('InnoDB');
            $table->foreignUuid('editor_id')->constrained('users')->restrictOnDelete();
            $table->foreignUuid('editing_tool_id')->constrained('editing_tools')->restrictOnDelete();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->primary(['editor_id', 'editing_tool_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('editor_tools');
    }
};
