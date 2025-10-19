<?php

namespace Database\Seeders;

use App\Models\Car;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DailyFleetMetricSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('daily_fleet_metrics')->truncate();

        $now = Carbon::now('Asia/Manila')->startOfDay();
        $totalUnits = Car::count();

        $records = [];
        for ($i = 0; $i < 14; $i++) {
            $date = (clone $now)->subDays($i);
            $unitsOut = max(0, min($totalUnits, (int) round($totalUnits * (0.65 + rand(0, 20) / 100))));
            $returnsDue = rand(1, 5);
            $maintenance = rand(0, 3);

            $records[] = [
                'snapshot_date' => $date->toDateString(),
                'total_units' => $totalUnits,
                'units_out' => $unitsOut,
                'returns_due_today' => $returnsDue,
                'utilization_pct' => round(($totalUnits > 0 ? $unitsOut / $totalUnits : 0) * 100, 2),
                'maintenance_count' => $maintenance,
                'notes' => json_encode([
                    'highlights' => $this->buildHighlight($unitsOut, $totalUnits),
                ]),
                'created_at' => $date->copy()->setHour(7),
                'updated_at' => $date->copy()->setHour(7),
            ];
        }

        DB::table('daily_fleet_metrics')->insert(array_reverse($records));
    }

    protected function buildHighlight(int $unitsOut, int $totalUnits): string
    {
        if ($totalUnits === 0) {
            return 'No vehicles in fleet.';
        }

        $util = $unitsOut / $totalUnits;
        if ($util >= 0.8) {
            return 'Strong utilization, monitor returns.';
        }

        if ($util >= 0.6) {
            return 'Healthy utilization.';
        }

        return 'Plenty of availability for new bookings.';
    }
}
