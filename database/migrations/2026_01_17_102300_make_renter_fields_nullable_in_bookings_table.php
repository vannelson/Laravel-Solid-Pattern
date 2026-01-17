<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('renter_first_name')->nullable()->change();
            $table->string('renter_middle_name')->nullable()->change();
            $table->string('renter_last_name')->nullable()->change();
            $table->string('renter_address')->nullable()->change();
            $table->string('renter_phone_number')->nullable()->change();
            $table->string('renter_email')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')->whereNull('renter_first_name')->update(['renter_first_name' => '']);
        DB::table('bookings')->whereNull('renter_middle_name')->update(['renter_middle_name' => '']);
        DB::table('bookings')->whereNull('renter_last_name')->update(['renter_last_name' => '']);
        DB::table('bookings')->whereNull('renter_address')->update(['renter_address' => '']);
        DB::table('bookings')->whereNull('renter_phone_number')->update(['renter_phone_number' => '']);
        DB::table('bookings')->whereNull('renter_email')->update(['renter_email' => '']);

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('renter_first_name')->nullable(false)->change();
            $table->string('renter_middle_name')->nullable(false)->change();
            $table->string('renter_last_name')->nullable(false)->change();
            $table->string('renter_address')->nullable(false)->change();
            $table->string('renter_phone_number')->nullable(false)->change();
            $table->string('renter_email')->nullable(false)->change();
        });
    }
};
