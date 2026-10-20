<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->foreignId('business_id')->nullable()->constrained('businesses')->nullOnDelete();
            $table->foreignId('expense_head_id')->nullable()->constrained('expense_heads')->nullOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->string('month', 10)->index(); // 'OCT', 'NOV', or 'ALL'
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->index(['depot_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budgets');
    }
};
