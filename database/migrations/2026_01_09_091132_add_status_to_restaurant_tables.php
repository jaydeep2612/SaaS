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
        Schema::table('restaurant_tables', function (Blueprint $table) {
           // 'available' = Green (Free), 'occupied' = Red (Customer Seated)
        $table->enum('status', ['available', 'occupied'])->default('available');
        
        // Stores the name of the customer currently occupying the table
        $table->string('current_customer_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('restaurant_tables', function (Blueprint $table) {
            //
        });
    }
};
