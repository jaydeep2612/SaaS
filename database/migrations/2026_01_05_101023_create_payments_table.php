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
        Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
    $table->foreignId('order_id')->constrained()->cascadeOnDelete();
    $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
    $table->decimal('amount', 8, 2);
    $table->enum('payment_method', ['cash','upi','card','online']);
    $table->enum('payment_status', ['pending','success','failed']);
    $table->string('transaction_id')->nullable();
    $table->timestamp('paid_at')->nullable();
    $table->foreignId('created_by')->constrained('users');
    $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
