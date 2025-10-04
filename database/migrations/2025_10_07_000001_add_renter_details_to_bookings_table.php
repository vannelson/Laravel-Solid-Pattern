<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('renter_first_name');
            $table->string('renter_middle_name');
            $table->string('renter_last_name');
            $table->string('renter_address');
            $table->string('renter_phone_number');
            $table->string('renter_email');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'renter_first_name',
                'renter_middle_name',
                'renter_last_name',
                'renter_address',
                'renter_phone_number',
                'renter_email',
            ]);
        });
    }
};

