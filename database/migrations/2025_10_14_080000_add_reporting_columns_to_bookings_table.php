<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'booking_code')) {
                $table->string('booking_code')
                    ->nullable()
                    ->unique()
                    ->after('id');
            }

            if (!Schema::hasColumn('bookings', 'duration_days')) {
                $table->unsignedSmallInteger('duration_days')
                    ->nullable()
                    ->after('end_date');
            }

            if (!Schema::hasColumn('bookings', 'pickup_location')) {
                $table->string('pickup_location')
                    ->nullable()
                    ->after('destination');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'booking_code')) {
                $table->dropUnique('bookings_booking_code_unique');
                $table->dropColumn('booking_code');
            }

            if (Schema::hasColumn('bookings', 'duration_days')) {
                $table->dropColumn('duration_days');
            }

            if (Schema::hasColumn('bookings', 'pickup_location')) {
                $table->dropColumn('pickup_location');
            }
        });
    }
};
