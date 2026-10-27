<?php

beforeEach(function () {
    $this->base = $this->useSandboxBasePath();
});

it('generates a filter', function () {
    $file = "{$this->base}/app/Filters/BookListFilter.php";

    $this->artisan('make:filter', ['name' => 'Book'])
        ->expectsOutput("Filter created: {$file}")
        ->assertSuccessful();

    $this->assertValidPhp($file);
    expect(file_get_contents($file))
        ->toContain('namespace App\Filters;')
        ->toContain('use JoBins\LaravelRepository\Filters\Filterable;')
        ->toContain('class BookListFilter extends Filterable')
        ->toContain('public function nameFilter(Builder $query, $value): Builder')
        ->not->toContain('{{');
});

it('places nested filters in matching directories and namespaces', function (string $name) {
    $file = "{$this->base}/app/Filters/Admin/BookListFilter.php";

    $this->artisan('make:filter', ['name' => $name])->assertSuccessful();

    $this->assertValidPhp($file);
    expect(file_get_contents($file))
        ->toContain('namespace App\Filters\Admin;')
        ->toContain('class BookListFilter extends Filterable');
})->with([
    'forward slash' => 'Admin/Book',
    'backslash' => 'Admin\Book',
    'lowercase' => 'admin/book',
]);

it('honours the configured filter path', function () {
    config()->set('repository.filter_path', app_path('Http/Filters'));

    $this->artisan('make:filter', ['name' => 'Book'])->assertSuccessful();

    $file = "{$this->base}/app/Http/Filters/BookListFilter.php";

    $this->assertValidPhp($file);
    expect(file_get_contents($file))->toContain('namespace App\Http\Filters;');
});

it('overwrites an existing filter file', function () {
    $file = "{$this->base}/app/Filters/BookListFilter.php";
    mkdir(dirname($file), 0755, true);
    file_put_contents($file, '<?php // custom code');

    $this->artisan('make:filter', ['name' => 'Book'])->assertSuccessful();

    expect(file_get_contents($file))
        ->not->toContain('custom code')
        ->toContain('class BookListFilter');
});
