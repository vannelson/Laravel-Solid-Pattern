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
            $table->dropForeign(['borrower_id']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('borrower_id')->nullable()->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('borrower_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['borrower_id']);
        });

        DB::table('bookings')
            ->whereNull('borrower_id')
            ->delete();

        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('borrower_id')->nullable(false)->change();
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->foreign('borrower_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }
};
