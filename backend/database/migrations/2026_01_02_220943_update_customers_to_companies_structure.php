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
        Schema::table('customers', function (Blueprint $table) {
            // Remove old individual-related fields
            $table->dropColumn(['name', 'whatsapp']);
            
            // Add new company fields
            $table->string('address')->nullable()->after('email');
            $table->string('phone')->nullable()->after('address');
            $table->boolean('is_individual')->default(false)->after('company');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Restore old fields
            $table->string('name')->after('id');
            $table->string('whatsapp')->nullable()->after('email');
            
            // Remove new fields
            $table->dropColumn(['address', 'phone', 'is_individual']);
        });
    }
};
