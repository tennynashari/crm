<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('master')->create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            
            // Authentication
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            
            // Status
            $table->boolean('is_active')->default(true);
            
            // Audit
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('company_id');
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::connection('master')->dropIfExists('users');
    }
};
