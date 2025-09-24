<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Repositories\Contracts\AlbumRepositoryInterface;
use App\Repositories\AlbumRepository;
use App\Services\Contracts\AlbumServiceInterface;
use App\Services\AlbumService;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Services\Contracts\UserServiceInterface;
use App\Services\UserService;

use App\Repositories\Contracts\ReactionRepositoryInterface;
use App\Repositories\ReactionRepository;
use App\Services\Contracts\ReactionServiceInterface;
use App\Services\ReactionService;

use App\Repositories\Contracts\SongRepositoryInterface;
use App\Repositories\SongRepository;
use App\Services\Contracts\SongServiceInterface;
use App\Services\SongService;

use App\Services\Contracts\CompanyServiceInterface;
use App\Services\CompanyService;
use App\Repositories\Contracts\CompanyRepositoryInterface;
use App\Repositories\companyRepository;

use App\Services\Contracts\CarServiceInterface;
use App\Services\CarService;
use App\Repositories\Contracts\CarRepositoryInterface;
use App\Repositories\CarRepository;


use App\Services\Contracts\CarRateServiceInterface;
use App\Services\CarRateService;
use App\Repositories\Contracts\CarRateRepositoryInterface;
use App\Repositories\CarRateRepository;


use App\Services\Contracts\BookingServiceInterface;
use App\Services\BookingService;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\BookingRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {

        // Bookings BookingServiceInterface and its implementation
        $this->app->bind(BookingServiceInterface::class, BookingService::class);
        $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);

        // CarRates CarRatesServiceInterface and its implementation
        $this->app->bind(CarRateServiceInterface::class, CarRateService::class);
        $this->app->bind(CarRateRepositoryInterface::class, CarRateRepository::class);
        
        // Car CarServiceInterface and its implementation
        $this->app->bind(CarServiceInterface::class, CarService::class);
        $this->app->bind(CarRepositoryInterface::class, CarRepository::class);

        // Registering CompanyServiceInterface and its implementation
        $this->app->bind(CompanyServiceInterface::class, CompanyService::class);
        $this->app->bind(CompanyRepositoryInterface::class, companyRepository::class);

        // Registering UserServiceInterface and its implementation
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);

        // Registering AlbumServiceInterface and its implementation
        $this->app->bind(AlbumServiceInterface::class, AlbumService::class);
        $this->app->bind(AlbumRepositoryInterface::class, AlbumRepository::class);

        // Registering ReactionServiceInterface and its implementation
        $this->app->bind(ReactionServiceInterface::class, ReactionService::class);
        $this->app->bind(ReactionRepositoryInterface::class, ReactionRepository::class);

        // Registering SongServiceInterface and its implementation
        $this->app->bind(SongServiceInterface::class, SongService::class);
        $this->app->bind(SongRepositoryInterface::class, SongRepository::class);
    }
}
