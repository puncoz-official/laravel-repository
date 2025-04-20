<?php

namespace JoBins\LaravelRepository;

use Illuminate\Support\ServiceProvider;
use JoBins\LaravelRepository\Console\Commands\MakeFilterCommand;
use JoBins\LaravelRepository\Console\Commands\MakeRepositoryCommand;
use JoBins\LaravelRepository\Console\Commands\MakeTransformerCommand;
use JoBins\LaravelRepository\Providers\VendorOverrideServiceProvider;
use JoBins\LaravelRepository\Providers\RepositoryEventServiceProvider;

/**
 * Class LaravelRepositoryServiceProvider
 *
 * @package JoBins\LaravelRepository
 */
class LaravelRepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRepositoryCommand::class,
                MakeTransformerCommand::class,
                MakeFilterCommand::class,
            ]);

            // Uncomment the following if you want to allow developers
            // to publish your stubs to their own application's folders.
            /*
            $this->publishes([
                __DIR__ . '/Console/stubs' => base_path('stubs/laravel-repository'),
            ], 'stubs');
            */
        }
        $this->publishes([
            __DIR__ . '/../config/repository.php' => config_path('repository.php'),
        ]);

        $this->mergeConfigFrom(__DIR__ . '/../config/repository.php', 'repository');

        /**
         * @var array<string, string> $bindings
         */
        $bindings = config('bindings.repositories');
        collect($bindings)->each(function (string $implementation, string $contract) {
            $this->app->bind($contract, $implementation);
        });

    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(RepositoryEventServiceProvider::class);
        $this->app->register(VendorOverrideServiceProvider::class);
    }
}