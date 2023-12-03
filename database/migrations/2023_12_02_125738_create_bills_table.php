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
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('number', 32)->unique();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('bill_date');
            $table->integer('total_price')->nullable();
            $table->integer('bill_discount')->nullable();
            $table->integer('final_price')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['new', 'pending', 'partial_paid', 'fully_paid'])->default('new');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
