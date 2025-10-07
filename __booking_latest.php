<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Booking;

foreach (Booking::orderByDesc('id')->take(20)->get() as $booking) {
    echo implode(' | ', [
        'ID:' . $booking->id,
        'Car:' . $booking->car_id,
        'Start:' . $booking->start_date,
        'End:' . $booking->end_date,
        'Status:' . $booking->status,
    ]) . "\n";
}