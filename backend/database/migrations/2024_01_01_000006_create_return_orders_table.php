<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->constrained()->onDelete('set null');
            $table->string('platform_return_id');
            $table->string('return_number');
            $table->string('type');
            $table->string('status');
            $table->text('reason')->nullable();
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->decimal('refund_amount', 10, 2)->default(0);
            $table->string('currency')->default('USD');
            $table->json('items')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('shipping_carrier')->nullable();
            $table->timestamp('return_date')->nullable();
            $table->timestamp('refund_date')->nullable();
            $table->timestamp('received_date')->nullable();
            $table->timestamp('restocked_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_orders');
    }
};
