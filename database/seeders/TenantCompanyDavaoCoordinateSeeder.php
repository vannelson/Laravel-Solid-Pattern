<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class TenantCompanyDavaoCoordinateSeeder extends Seeder
{
    public function run(): void
    {
        $coordinates = [
            'Metro Mobility Fleet'                => [7.1662, 125.9015],
            'Airport Shuttle Partners'            => [7.1708, 125.9089],
            'VisMin Executive Transport'          => [7.1594, 125.8921],
            'NorthLink Fleet'                     => [7.1765, 125.8857],
            'SouthPoint Mobility'                 => [7.1498, 125.9033],
            'Island Hopper Transport'             => [7.1541, 125.9186],
            'Prime Horizon Fleet'                 => [7.1629, 125.9135],
            'Skyway Services'                     => [7.1476, 125.8879],
            'GreenRoute Vans'                     => [7.1831, 125.9092],
            'CityLink Mobility'                   => [7.1701, 125.8964],
            'MetroEast Shuttle'                   => [7.1527, 125.8805],
            'WestDrive Vans'                      => [7.1815, 125.8932],
            'Laguna Loop Transit'                 => [7.1640, 125.9225],
            'Northern Wheels'                     => [7.1883, 125.9026],
            'Sunrise Fleet'                       => [7.1954, 125.9141],
            'Coastline Mobility'                  => [7.1588, 125.8844],
            'Metro Mobility Fleet - South'        => [7.1405, 125.8977],
            'Airport Shuttle Partners - Cebu'     => [7.1738, 125.9198],
        ];

        foreach ($coordinates as $companyName => [$latitude, $longitude]) {
            Company::where('name', $companyName)->update([
                'latitude'  => $latitude,
                'longitude' => $longitude,
            ]);
        }
    }
}
