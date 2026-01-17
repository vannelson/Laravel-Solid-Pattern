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
            $table->string('identification_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')
            ->whereNull('identification_type')
            ->update(['identification_type' => '']);

        Schema::table('bookings', function (Blueprint $table) {
            $table->string('identification_type')->nullable(false)->change();
        });
    }
};
