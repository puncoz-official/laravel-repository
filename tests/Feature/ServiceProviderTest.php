<?php

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use JoBins\LaravelRepository\Fractal\CustomParamBag;
use JoBins\LaravelRepository\Fractal\CustomScope;
use JoBins\LaravelRepository\LaravelRepositoryServiceProvider;
use JoBins\LaravelRepository\Providers\RepositoryEventServiceProvider;
use JoBins\LaravelRepository\Providers\VendorOverrideServiceProvider;
use JoBins\LaravelRepository\Tests\Fixtures\Repositories\PostRepository;
use League\Fractal\ParamBag;
use League\Fractal\Scope;

it('registers the package service provider', function () {
    expect($this->app->getProviders(LaravelRepositoryServiceProvider::class))->not->toBeEmpty();
});

it('registers its nested service providers', function (string $provider) {
    expect($this->app->getProviders($provider))->not->toBeEmpty();
})->with([
    'event provider'           => RepositoryEventServiceProvider::class,
    'vendor override provider' => VendorOverrideServiceProvider::class,
]);

it('registers the generator commands', function (string $command) {
    expect(Artisan::all())->toHaveKey($command);
})->with(['make:repository', 'make:transformer', 'make:filter']);

it('merges the package configuration', function () {
    expect(config('repository.pagination.limit'))->toBe(10);
});

it('publishes the configuration file', function () {
    $paths = ServiceProvider::pathsToPublish(LaravelRepositoryServiceProvider::class);
    $source = realpath(__DIR__ . '/../../config/repository.php');

    $published = collect($paths)->mapWithKeys(fn(string $target, string $from) => [realpath($from) => $target]);

    expect($published->get($source))->toBe(config_path('repository.php'));
});

it('aliases the fractal scope and param bag to the package overrides', function () {
    $aliases = AliasLoader::getInstance()->getAliases();

    expect($aliases)
        ->toHaveKey(Scope::class, CustomScope::class)
        ->toHaveKey(ParamBag::class, CustomParamBag::class);
});

it('binds repositories declared in the bindings config', function () {
    config()->set('bindings.repositories', ['App\Contracts\PostRepository' => PostRepository::class]);

    (new LaravelRepositoryServiceProvider($this->app))->boot();

    expect($this->app->make('App\Contracts\PostRepository'))->toBeInstanceOf(PostRepository::class);
});

it('boots without a bindings config', function () {
    config()->set('bindings.repositories', null);

    (new LaravelRepositoryServiceProvider($this->app))->boot();

    expect($this->app->bound('App\Contracts\PostRepository'))->toBeFalse();
});
