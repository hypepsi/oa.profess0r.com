<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->string('message_id')->nullable();        // RFC Message-ID header
            $table->string('uid')->nullable();               // IMAP UID
            $table->string('folder')->default('INBOX');
            $table->string('subject')->nullable();
            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->json('to_addresses')->nullable();        // [{name, email}, ...]
            $table->json('cc_addresses')->nullable();
            $table->json('bcc_addresses')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('body_text')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->string('direction')->default('inbound'); // inbound | outbound
            $table->text('ai_summary')->nullable();          // DeepSeek summary in Chinese
            $table->timestamp('ai_summarized_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['email_account_id', 'uid', 'folder']);
            $table->index(['email_account_id', 'folder', 'sent_at']);
            $table->index(['email_account_id', 'is_read']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_messages');
    }
};
