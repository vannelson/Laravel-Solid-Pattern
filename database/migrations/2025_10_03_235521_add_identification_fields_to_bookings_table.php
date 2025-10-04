<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('identification_type')->after('destination');
            $table->string('identification')->after('identification_type');
            $table->string('identification_number')->after('identification');
            $table->json('identification_images')->nullable()->after('identification_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'identification_type',
                'identification',
                'identification_number',
                'identification_images',
            ]);
        });
    }
};
