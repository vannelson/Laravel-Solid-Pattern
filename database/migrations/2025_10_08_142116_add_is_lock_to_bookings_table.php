<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'is_lock')) {
                $anchorColumn = Schema::hasColumn('bookings', 'renter_email') ? 'renter_email' : 'status';
                $table->boolean('is_lock')->default(false)->after($anchorColumn);
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'is_lock')) {
                $table->dropColumn('is_lock');
            }
        });
    }
};
