<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Transport / carriage mode logging retired — the field is removed
            // from the entry form, exports, and dashboards.
            $table->dropColumn('transport_mode');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('transport_mode')->nullable()->after('amount');
        });
    }
};
