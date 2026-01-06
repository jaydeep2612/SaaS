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
        Schema::create('orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('table_id')->constrained('restaurant_tables');
    $table->enum('status', [
        'placed',
        'preparing',
        'ready',
        'served',
        'completed'
    ])->default('placed');
    $table->decimal('total_amount', 8, 2)->default(0);
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
