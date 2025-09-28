<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workflow_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message')->nullable();         // Update message text
            $table->json('attachments')->nullable();     // File paths (array)
            $table->timestamps();

            $table->index(['workflow_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_updates');
    }
};
