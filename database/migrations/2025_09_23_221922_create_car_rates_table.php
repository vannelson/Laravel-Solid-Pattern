<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->decimal('rate', 10, 2);
            $table->string('rate_type')->default('daily'); 
            $table->date('start_date')->nullable();

            $table->enum('status', ['active', 'inactive', 'scheduled'])->default('inactive');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_rates');
    }
};
