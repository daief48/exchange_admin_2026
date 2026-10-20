<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('depot_id')->nullable()->after('password')->constrained('depots')->nullOnDelete();
            $table->string('role')->default('depot_operator')->after('depot_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['depot_id']);
            $table->dropColumn(['depot_id', 'role']);
        });
    }
};
