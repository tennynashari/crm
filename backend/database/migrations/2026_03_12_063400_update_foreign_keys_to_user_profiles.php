<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Update foreign keys from 'users' table to 'user_profiles' table
     * This is for multi-tenant architecture where user profiles are in tenant DB
     */
    public function up(): void
    {
        // 1. Drop old foreign key constraints referencing 'users' table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['assigned_sales_id']);
        });
        
        Schema::table('interactions', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        // 2. Create new foreign key constraints referencing 'user_profiles' table
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('assigned_sales_id')
                ->references('id')
                ->on('user_profiles')
                ->nullOnDelete();
        });
        
        Schema::table('interactions', function (Blueprint $table) {
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('user_profiles')
                ->nullOnDelete();
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('user_profiles')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 'users' table references
        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['assigned_sales_id']);
        });
        
        Schema::table('interactions', function (Blueprint $table) {
            $table->dropForeign(['created_by_user_id']);
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        
        Schema::table('customers', function (Blueprint $table) {
            $table->foreign('assigned_sales_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
        
        Schema::table('interactions', function (Blueprint $table) {
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
        
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
