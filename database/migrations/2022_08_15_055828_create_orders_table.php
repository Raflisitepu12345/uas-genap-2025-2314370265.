<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('code')->unique();
            $table->enum('status', ['pending', 'paid', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->timestamp('order_date')->nullable();
            $table->timestamp('payment_due')->nullable();
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->decimal('grand_total', 10, 2);
            $table->string('customer_first_name');
            $table->string('customer_address');
            $table->string('customer_phone');
            $table->string('customer_email');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
