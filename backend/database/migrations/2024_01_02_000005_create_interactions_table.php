<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('interaction_type', ['email_inbound', 'email_outbound', 'manual_channel', 'note']);
            $table->enum('channel', [
                'email',
                'whatsapp',
                'telephone',
                'instagram',
                'tiktok',
                'tokopedia',
                'shopee',
                'lazada',
                'website_chat',
                'other'
            ])->nullable();
            $table->string('subject')->nullable();
            $table->text('content')->nullable();
            $table->text('summary')->nullable();
            $table->enum('created_by_type', ['user', 'system'])->default('user');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_status_snapshot_id')->nullable()->constrained('lead_statuses')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('interaction_at')->useCurrent();
            $table->timestamps();
            
            $table->index(['customer_id', 'interaction_type']);
            $table->index('interaction_at');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactions');
    }
};
