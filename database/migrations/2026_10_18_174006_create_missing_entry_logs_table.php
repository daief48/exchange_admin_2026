<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('missing_entry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->date('date')->index();
            $table->timestamp('sent_at')->nullable();
            $table->text('recipients')->nullable();
            $table->timestamps();

            $table->unique(['depot_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missing_entry_logs');
    }
};
