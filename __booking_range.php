<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Booking;

$start = '2025-10-14 00:00:00';
$end = '2025-10-16 23:59:59';

$bookings = Booking::where('start_date', '<=', $end)
    ->whereRaw('COALESCE(end_date, expected_return_date, start_date) >= ?', [$start])
    ->get(['id','car_id','start_date','end_date','expected_return_date','status']);

foreach ($bookings as $booking) {
    echo implode(' | ', [
        'Booking:' . $booking->id,
        'Car:' . $booking->car_id,
        'Start:' . $booking->start_date,
        'End:' . $booking->end_date,
        'Expected:' . $booking->expected_return_date,
        'Status:' . $booking->status,
    ]) . "\n";
}