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
        Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->decimal('subtotal', 8, 2);
    $table->decimal('tax', 8, 2)->default(0);
    $table->decimal('discount', 8, 2)->default(0);
    $table->decimal('final_amount', 8, 2);
    $table->enum('payment_status', ['unpaid','paid','partial'])->default('unpaid');
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
