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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->integer('service_id');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('restrict');
            $table->string('brand', 100);
            $table->string('model', 100)->nullable();
            $table->string('color', 50);
            $table->string('material', 100);
            $table->integer('size')->nullable();
            $table->text('initial_condition_notes')->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
