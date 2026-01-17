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
            $table->string('identification')->nullable()->change();
            $table->string('identification_number')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')
            ->whereNull('identification')
            ->update(['identification' => '']);

        DB::table('bookings')
            ->whereNull('identification_number')
            ->update(['identification_number' => '']);

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('identification')->nullable(false)->change();
            $table->string('identification_number')->nullable(false)->change();
        });
    }
};
