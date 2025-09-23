<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();

            // Relation to company
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Basic Info (info_)
            $table->string('info_make')->nullable();
            $table->string('info_model')->nullable();
            $table->year('info_year')->nullable();
            $table->string('info_age')->nullable();
            $table->string('info_carType')->nullable();
            $table->string('info_plateNumber')->unique();
            $table->string('info_vin')->unique();
            $table->string('info_availabilityStatus')->default('Available');
            $table->string('info_location')->nullable();
            $table->integer('info_mileage')->default(0);

            // Specifications (spcs_)
            $table->integer('spcs_seats')->default(4);
            $table->integer('spcs_largeBags')->default(0);
            $table->integer('spcs_smallBags')->default(0);
            $table->integer('spcs_engineSize')->nullable(); // cc
            $table->string('spcs_transmission')->nullable(); // Automatic / Manual
            $table->string('spcs_fuelType')->nullable(); // Petrol / Diesel / Hybrid / Electric
            $table->decimal('spcs_fuelEfficiency', 5, 2)->nullable(); // L/100km

            // Features & Images
            $table->json('features')->nullable();        // list of features
            $table->string('profileImage')->nullable();  // main car image
            $table->json('displayImages')->nullable();   // gallery

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
