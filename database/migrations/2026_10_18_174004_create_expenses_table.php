<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('month', 10)->index();
            $table->unsignedSmallInteger('year')->index();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->foreignId('expense_head_id')->constrained('expense_heads')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('transport_mode')->nullable();
            $table->text('remarks')->nullable();
            $table->string('voucher_path')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['depot_id', 'date']);
            $table->index(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
