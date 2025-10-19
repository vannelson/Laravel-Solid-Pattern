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
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('metric_date')->index();
            $table->timestamp('captured_at')->index();
            $table->unsignedInteger('total_units');
            $table->unsignedInteger('active_units')->default(0);
            $table->unsignedInteger('available_units')->default(0);
            $table->unsignedInteger('returns_due_today')->default(0);
            $table->unsignedInteger('outstanding_maintenance')->default(0);
            $table->decimal('utilization_pct', 5, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'metric_date'], 'daily_fleet_metrics_tenant_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_fleet_metrics');
    }
};

