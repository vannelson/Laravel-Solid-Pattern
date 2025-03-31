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

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
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
