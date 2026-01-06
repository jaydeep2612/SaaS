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
        Schema::table('users', function (Blueprint $table) {
    $table->foreignId('restaurant_id')
        ->nullable()
        ->after('id')
        ->constrained()
        ->nullOnDelete();

    $table->enum('role', [
        'super_admin',
        'admin',
        'manager',
        'chef',
        'waiter'
    ])->after('email');

    $table->foreignId('created_by')
        ->nullable()
        ->after('role')
        ->constrained('users')
        ->nullOnDelete();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
