<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company')->nullable();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email')->nullable();
            $table->string('whatsapp')->nullable();
            $table->enum('source', ['inbound', 'outbound'])->default('inbound');
            $table->foreignId('assigned_sales_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_status_id')->nullable()->constrained()->nullOnDelete();
            
            // Next Action Fields
            $table->date('next_action_date')->nullable();
            $table->text('next_action_plan')->nullable();
            $table->enum('next_action_priority', ['low', 'medium', 'high'])->nullable();
            $table->enum('next_action_status', ['pending', 'done', 'overdue'])->default('pending');
            
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['assigned_sales_id', 'lead_status_id']);
            $table->index('area_id');
            $table->index('next_action_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
