<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('master')->create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('database_name', 100)->unique();
            
            // Contact info
            $table->string('email')->nullable();
            $table->string('phone', 50)->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->string('subscription_status', 50)->default('trial');
            $table->timestamp('subscription_expires_at')->nullable();
            
            // Limits
            $table->integer('max_users')->default(10);
            $table->integer('max_customers')->default(1000);
            
            // Settings
            $table->json('settings')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('database_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('master')->dropIfExists('companies');
    }
};
