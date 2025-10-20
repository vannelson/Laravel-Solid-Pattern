<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->index(['tenant_id', 'status'], 'bookings_tenant_status_index');
            $table->index('actual_return_date', 'bookings_actual_return_date_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('paid_at', 'payments_paid_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_tenant_status_index');
            $table->dropIndex('bookings_actual_return_date_index');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_paid_at_index');
        });
    }
};
