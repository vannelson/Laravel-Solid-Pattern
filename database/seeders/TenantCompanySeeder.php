<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantCompanySeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $tenants = $this->seedTenants();

            $this->seedCompanies($tenants);
        });
    }

    /**
     * Seed twenty tenant users with realistic Philippine contact data.
     *
     * @return array<int, \App\Models\User>
     */
    protected function seedTenants(): array
    {
        $profiles = [
            ['Lia', 'Fernandez', 'Santos', 'lia.santos@fleetdemo.test', '+639171000101', 'Ortigas Center, Pasig City'],
            ['Rico', 'Alcaraz', 'Velasco', 'rico.velasco@fleetdemo.test', '+639171000102', 'Makati Central Business District'],
            ['Kei', 'Baltazar', 'Soriano', 'kei.soriano@fleetdemo.test', '+639171000103', 'Bonifacio Global City, Taguig'],
            ['Nina', 'Reyes', 'Cortez', 'nina.cortez@fleetdemo.test', '+639171000104', 'Cebu IT Park, Cebu City'],
            ['Drew', 'Garcia', 'Tan', 'drew.tan@fleetdemo.test', '+639171000105', 'Davao City Business District'],
            ['Marco', 'Ibarra', 'Yap', 'marco.yap@fleetdemo.test', '+639171000106', 'Clark Freeport Zone, Pampanga'],
            ['Siena', 'Marquez', 'Ramos', 'siena.ramos@fleetdemo.test', '+639171000107', 'Iloilo Business Park, Iloilo City'],
            ['Xavi', 'Torres', 'Chua', 'xavi.chua@fleetdemo.test', '+639171000108', 'Ayala Center, Cebu City'],
            ['Aly', 'Romero', 'Castillo', 'aly.castillo@fleetdemo.test', '+639171000109', 'Paseo de Roxas, Makati'],
            ['Gabe', 'Flores', 'Del Rosario', 'gabe.delrosario@fleetdemo.test', '+639171000110', 'Timog Avenue, Quezon City'],
            ['Mara', 'Uy', 'Lopez', 'mara.lopez@fleetdemo.test', '+639171000111', 'Greenhills, San Juan'],
            ['Noel', 'Ibanez', 'Lim', 'noel.lim@fleetdemo.test', '+639171000112', 'Alabang Town Center, Muntinlupa'],
            ['Iris', 'Navarro', 'Dizon', 'iris.dizon@fleetdemo.test', '+639171000113', 'Nuvali, Sta. Rosa'],
            ['Theo', 'Villanueva', 'Limbo', 'theo.limbo@fleetdemo.test', '+639171000114', 'Commonwealth, Quezon City'],
            ['Pia', 'Carreon', 'Santos', 'pia.santos@fleetdemo.test', '+639171000115', 'Ortigas Avenue, Pasig City'],
            ['Jett', 'Briones', 'Ang', 'jett.ang@fleetdemo.test', '+639171000116', 'Binondo, Manila'],
            ['Belle', 'Aquino', 'Reyes', 'belle.reyes@fleetdemo.test', '+639171000117', 'Meralco Avenue, Pasig'],
            ['Owen', 'Chan', 'Go', 'owen.go@fleetdemo.test', '+639171000118', 'West Avenue, Quezon City'],
            ['Trixie', 'Domingo', 'Ferrer', 'trixie.ferrer@fleetdemo.test', '+639171000119', 'Kapitolyo, Pasig City'],
            ['Andrei', 'Mendoza', 'Del Mundo', 'andrei.delmundo@fleetdemo.test', '+639171000120', 'Ortigas East, Pasig City'],
        ];

        return array_map(static function (array $profile) {
            [$first, $middle, $last, $email, $phone, $address] = $profile;

            return User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name'   => $first,
                    'middle_name'  => $middle,
                    'last_name'    => $last,
                    'password'     => Hash::make('TenantPass123!'),
                    'type'         => 'tenant',
                    'role'         => 'manager',
                    'phone_number' => $phone,
                    'address'      => $address,
                ]
            );
        }, $profiles);
    }

    /**
     * Assign mostly single-company ownership, with two tenants handling two companies.
     *
     * @param array<int, \App\Models\User> $tenants
     */
    protected function seedCompanies(array $tenants): void
    {
        $tenantCompanies = [
            ['Metro Mobility Fleet', 'Ortigas Center, Pasig', 'Corporate Shuttle', 14.5869, 121.0617],
            ['Airport Shuttle Partners', 'NAIA Terminal 3, Pasay', 'Travel', 14.5123, 121.0197],
            ['VisMin Executive Transport', 'Cebu IT Park, Cebu City', 'Executive Transport', 10.3247, 123.9050],
            ['NorthLink Fleet', 'UP Technohub, Quezon City', 'Staff Transport', 14.6573, 121.0647],
            ['SouthPoint Mobility', 'Alabang Business District', 'Logistics', 14.4170, 121.0415],
            ['Island Hopper Transport', 'Lapu-Lapu City, Cebu', 'Tourism', 10.3103, 123.9495],
            ['Prime Horizon Fleet', 'Global City, Taguig', 'Corporate', 14.5410, 121.0437],
            ['Skyway Services', 'Skyway, Muntinlupa', 'Airport Runs', 14.4076, 121.0461],
            ['GreenRoute Vans', 'Nuvali, Sta. Rosa', 'Shuttle', 14.2545, 121.0569],
            ['CityLink Mobility', 'Makati CBD', 'Corporate', 14.5547, 121.0198],
            ['MetroEast Shuttle', 'Marikina City', 'Commuter', 14.6507, 121.1029],
            ['WestDrive Vans', 'West Avenue, QC', 'Operations', 14.6469, 121.0281],
            ['Laguna Loop Transit', 'Sta. Rosa, Laguna', 'Industrial', 14.2786, 121.0894],
            ['Northern Wheels', 'Baguio City', 'Tourist', 16.4023, 120.5960],
            ['Sunrise Fleet', 'Clark Freeport, Pampanga', 'Logistics', 15.1860, 120.5606],
            ['Coastline Mobility', 'Subic Bay Freeport', 'Port Services', 14.7870, 120.2510],
        ];

        foreach ($tenantCompanies as $index => [$name, $address, $industry, $latitude, $longitude]) {
            $tenant = $tenants[$index % count($tenants)];

            Company::updateOrCreate(
                [
                    'user_id' => $tenant->id,
                    'name'    => $name,
                ],
                [
                    'address'    => $address,
                    'industry'   => $industry,
                    'latitude'   => $latitude,
                    'longitude'  => $longitude,
                    'is_default' => true,
                ]
            );
        }

        // Give the first two tenants a second company each.
        $bonusCompanies = [
            [$tenants[0], 'Metro Mobility Fleet - South', 'Paranaque City', 'Corporate Shuttle', 14.4793, 120.9820],
            [$tenants[1], 'Airport Shuttle Partners - Cebu', 'Mactan Cebu Airport', 'Travel', 10.3133, 123.9827],
        ];

        foreach ($bonusCompanies as [$tenant, $name, $address, $industry, $latitude, $longitude]) {
            Company::updateOrCreate(
                [
                    'user_id' => $tenant->id,
                    'name'    => $name,
                ],
                [
                    'address'    => $address,
                    'industry'   => $industry,
                    'latitude'   => $latitude,
                    'longitude'  => $longitude,
                    'is_default' => false,
                ]
            );
        }
    }
}

