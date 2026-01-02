<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_id')->unique();
            $table->string('from_email');
            $table->string('from_name')->nullable();
            $table->text('to_emails'); // JSON array
            $table->text('cc_emails')->nullable(); // JSON array
            $table->text('bcc_emails')->nullable(); // JSON array
            $table->string('subject')->nullable();
            $table->text('body_text')->nullable();
            $table->text('body_html')->nullable();
            $table->boolean('is_inbound')->default(true);
            $table->boolean('is_processed')->default(false);
            $table->json('raw_headers')->nullable();
            $table->text('raw_body')->nullable();
            $table->timestamp('email_date');
            $table->timestamps();
            
            $table->index(['email_account_id', 'is_processed']);
            $table->index('customer_id');
            $table->index('email_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('emails');
    }
};
