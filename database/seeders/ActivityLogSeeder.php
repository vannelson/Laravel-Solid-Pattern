<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Car;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('activity_logs')->truncate();

        $now = Carbon::now('Asia/Manila');
        $bookings = Booking::query()->latest('start_date')->take(10)->get();
        $cars = Car::all()->keyBy('id');
        $users = User::query()->whereIn('type', ['tenant', 'borrower'])->get()->keyBy('id');

        $templates = [
            ['event_type' => 'success', 'title' => 'New corporate reservation', 'description' => '5-day booking confirmed'],
            ['event_type' => 'info', 'title' => 'Vehicle dispatched', 'description' => 'Driver departed for pickup schedule'],
            ['event_type' => 'warning', 'title' => 'Late return flagged', 'description' => 'Vehicle has not returned as scheduled'],
            ['event_type' => 'success', 'title' => 'Payment received', 'description' => 'Full payment posted for booking'],
            ['event_type' => 'info', 'title' => 'Maintenance reminder', 'description' => 'Vehicle due for oil change next week'],
            ['event_type' => 'warning', 'title' => 'Insurance expiration', 'description' => 'Insurance expiring within 14 days'],
            ['event_type' => 'success', 'title' => 'Return completed', 'description' => 'Vehicle inspected and cleared for next booking'],
            ['event_type' => 'info', 'title' => 'Customer feedback logged', 'description' => 'Guest rated the trip 5 stars'],
        ];

        $records = [];
        foreach ($templates as $index => $template) {
            $booking = $bookings[$index % max($bookings->count(), 1)] ?? null;
            $carId = $booking->car_id ?? $cars->keys()->random();
            $userId = $booking?->tenant_id ?? $users->keys()->random();

            $records[] = [
                'booking_id' => $booking?->id,
                'car_id' => $carId,
                'user_id' => $userId,
                'event_type' => $template['event_type'],
                'title' => $template['title'],
                'description' => $this->buildDescription($template['description'], $booking, $cars->get($carId)),
                'occurred_at' => $now->copy()->subHours($index * 3 + rand(0, 2)),
                'meta' => json_encode([
                    'source' => 'dashboard-seeder',
                    'importance' => $index <= 2 ? 'high' : 'normal',
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('activity_logs')->insert($records);
    }

    protected function buildDescription(string $base, ?Booking $booking, ?Car $car): string
    {
        $vehicle = $car
            ? sprintf('%s %s (%s)', $car->info_make, $car->info_model, $car->info_plateNumber)
            : 'Fleet vehicle';

        if ($booking) {
            return sprintf(
                '%s for booking #%d using %s.',
                $base,
                $booking->id,
                $vehicle
            );
        }

        return sprintf('%s for %s.', $base, $vehicle);
    }
}
