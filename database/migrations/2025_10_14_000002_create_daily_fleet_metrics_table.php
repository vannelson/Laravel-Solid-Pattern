<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_fleet_metrics', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date')->unique();
            $table->unsignedInteger('total_units');
            $table->unsignedInteger('units_out');
            $table->unsignedInteger('returns_due_today')->default(0);
            $table->decimal('utilization_pct', 5, 2)->default(0);
            $table->unsignedInteger('maintenance_count')->default(0);
            $table->json('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_fleet_metrics');
    }
};
