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
        Schema::create('broadcast_email_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('subject');
            $table->text('body');
            $table->string('filter_type'); // 'all' or 'area'
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('set null');
            $table->integer('recipient_count')->default(0);
            $table->json('recipients'); // Array of email addresses
            $table->boolean('has_attachments')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('broadcast_email_history');
    }
};
