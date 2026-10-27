<?php

namespace JoBins\LaravelRepository\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use JoBins\LaravelRepository\LaravelRepositoryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use ParseError;

abstract class TestCase extends Orchestra
{
    protected ?string $sandboxPath = null;

    protected function tearDown(): void
    {
        if ( $this->sandboxPath !== null ) {
            (new Filesystem())->deleteDirectory($this->sandboxPath);
            $this->sandboxPath = null;
        }

        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [LaravelRepositoryServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    /**
     * Points the application at a throwaway directory so generator commands never write
     * into the shared Testbench skeleton.
     */
    protected function useSandboxBasePath(): string
    {
        $this->sandboxPath = sys_get_temp_dir() . '/laravel-repository-' . bin2hex(random_bytes(6));
        mkdir($this->sandboxPath, 0755, true);

        $this->app->setBasePath($this->sandboxPath);

        return $this->sandboxPath;
    }

    protected function assertValidPhp(string $path): void
    {
        $this->assertFileExists($path);

        try {
            token_get_all((string) file_get_contents($path), TOKEN_PARSE);
        } catch (ParseError $error) {
            $this->fail("Generated file {$path} is not valid PHP: {$error->getMessage()}");
        }
    }

    protected function createBlogSchema(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status')->default('draft');
            $table->unsignedInteger('views')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id');
            $table->string('body');
            $table->timestamps();
        });
    }
}
