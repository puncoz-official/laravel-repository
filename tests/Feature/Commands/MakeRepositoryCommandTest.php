<?php

beforeEach(function () {
    $this->base = $this->useSandboxBasePath();
});

it('generates a repository and its interface', function () {
    $this->artisan('make:repository', ['name' => 'User'])
        ->expectsOutput("Repository created: {$this->base}/app/Repositories/UserEloquentRepository.php")
        ->expectsOutput("Interface created: {$this->base}/app/Repositories/UserRepository.php")
        ->assertSuccessful();

    $repository = "{$this->base}/app/Repositories/UserEloquentRepository.php";
    $interface = "{$this->base}/app/Repositories/UserRepository.php";

    $this->assertValidPhp($repository);
    $this->assertValidPhp($interface);

    expect(file_get_contents($repository))
        ->toContain('namespace App\Repositories;')
        ->toContain('use App\Models\User;')
        ->toContain('use App\Repositories\UserRepository;')
        ->toContain('class UserEloquentRepository extends Repository implements UserRepository')
        ->toContain('return User::class;')
        ->not->toContain('{{');

    expect(file_get_contents($interface))
        ->toContain('namespace App\Repositories;')
        ->toContain('interface UserRepository extends RepositoryInterface')
        ->not->toContain('{{');
});

it('places nested repositories in matching directories and namespaces', function (string $name) {
    $this->artisan('make:repository', ['name' => $name])->assertSuccessful();

    $repository = "{$this->base}/app/Repositories/Common/ItemEloquentRepository.php";

    $this->assertValidPhp($repository);
    $this->assertValidPhp("{$this->base}/app/Repositories/Common/ItemRepository.php");

    expect(file_get_contents($repository))
        ->toContain('namespace App\Repositories\Common;')
        ->toContain('use App\Models\Common\Item;')
        ->toContain('use App\Repositories\Common\ItemRepository;');
})->with([
    'studly name' => 'Common/Item',
    'lowercase name' => 'common/item',
]);

it('uses the model given by the --model option', function () {
    $this->artisan('make:repository', ['name' => 'Admin/User', '--model' => 'Account'])->assertSuccessful();

    expect(file_get_contents("{$this->base}/app/Repositories/Admin/UserEloquentRepository.php"))
        ->toContain('use App\Models\Admin\Account;')
        ->toContain('return Account::class;')
        ->toContain('class UserEloquentRepository extends Repository implements UserRepository');
});

it('puts the interface in an Interfaces subfolder when configured', function () {
    config()->set('repository.interface_subfolder', true);

    $this->artisan('make:repository', ['name' => 'Common/Item'])
        ->expectsOutput("Interface created: {$this->base}/app/Repositories/Common/Interfaces/ItemRepository.php")
        ->assertSuccessful();

    $interface = "{$this->base}/app/Repositories/Common/Interfaces/ItemRepository.php";

    $this->assertValidPhp($interface);
    expect(file_get_contents($interface))->toContain('namespace App\Repositories\Common\Interfaces;');
    expect(file_get_contents("{$this->base}/app/Repositories/Common/ItemEloquentRepository.php"))
        ->toContain('namespace App\Repositories\Common;')
        ->toContain('use App\Repositories\Common\Interfaces\ItemRepository;');
    expect("{$this->base}/app/Repositories/Common/ItemRepository.php")->not->toBeFile();
});

it('honours the configured path and suffixes', function () {
    config()->set('repository.repository_path', 'app/Data/Repos');
    config()->set('repository.repository_suffix', 'Repo');
    config()->set('repository.interface_suffix', 'Contract');

    $this->artisan('make:repository', ['name' => 'Order'])->assertSuccessful();

    $repository = "{$this->base}/app/Data/Repos/OrderRepo.php";

    $this->assertValidPhp($repository);
    $this->assertValidPhp("{$this->base}/app/Data/Repos/OrderContract.php");
    expect(file_get_contents($repository))
        ->toContain('namespace App\Data\Repos;')
        ->toContain('class OrderRepo extends Repository implements OrderContract');
});

it('overwrites an existing repository file', function () {
    $repository = "{$this->base}/app/Repositories/UserEloquentRepository.php";
    mkdir(dirname($repository), 0755, true);
    file_put_contents($repository, '<?php // custom code');

    $this->artisan('make:repository', ['name' => 'User'])->assertSuccessful();

    expect(file_get_contents($repository))
        ->not->toContain('custom code')
        ->toContain('class UserEloquentRepository');
});
