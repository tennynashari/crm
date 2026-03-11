<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('master_user_id')->unique();
            
            // Profile info
            $table->string('name');
            $table->string('email');
            $table->string('phone', 50)->nullable();
            $table->string('avatar_url', 500)->nullable();
            
            // Role & Permissions
            $table->enum('role', ['admin', 'sales', 'marketing', 'manager'])->default('sales');
            $table->json('permissions')->nullable();
            
            // Preferences
            $table->string('language', 10)->default('id');
            $table->string('timezone', 50)->default('Asia/Jakarta');
            $table->json('notifications')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('master_user_id');
            $table->index('email');
            $table->index('role');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('user_profiles');
    }
};
