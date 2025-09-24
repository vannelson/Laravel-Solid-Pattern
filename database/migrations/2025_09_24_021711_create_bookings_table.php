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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->foreignId('borrower_id')->constrained('users')->onDelete('cascade'); // borrower user
            $table->foreignId('tenant_id')->nullable()->constrained('users')->onDelete('set null'); // staff/admin

            // Booking dates
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->dateTime('expected_return_date');
            $table->dateTime('actual_return_date')->nullable();

            // Borrower destination
            $table->string('destination')->nullable();

            // Rate snapshot
            $table->string('rate_type'); // daily/hourly
            $table->decimal('rate', 10, 2);

            // Financials
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('extra_payment', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Status
            $table->enum('payment_status', ['Pending', 'Paid', 'Cancelled'])->default('Pending');
            $table->enum('status', ['Reserved', 'Ongoing', 'Completed', 'Cancelled'])->default('Reserved');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
