<?php

beforeEach(function () {
    $this->base = $this->useSandboxBasePath();
});

it('generates a transformer', function () {
    $file = "{$this->base}/app/Transformers/BookTransformer.php";

    $this->artisan('make:transformer', ['name' => 'Book'])
        ->expectsOutput("Transformer created: {$file}")
        ->assertSuccessful();

    $this->assertValidPhp($file);
    expect(file_get_contents($file))
        ->toContain('namespace App\Transformers;')
        ->toContain('use App\Models\Book;')
        ->toContain('class BookTransformer extends TransformerAbstract')
        ->toContain('public function transform(Book $book): array')
        ->not->toContain('{{');
});

it('places nested transformers in matching directories and namespaces', function (string $name) {
    $file = "{$this->base}/app/Transformers/Common/BookItemTransformer.php";

    $this->artisan('make:transformer', ['name' => $name])->assertSuccessful();

    $this->assertValidPhp($file);
    expect(file_get_contents($file))
        ->toContain('namespace App\Transformers\Common;')
        ->toContain('use App\Models\Common\BookItem;')
        ->toContain('public function transform(BookItem $bookItem): array')
        ->toContain('$bookItem->id');
})->with([
    'forward slash' => 'Common/BookItem',
    'backslash'     => 'Common\BookItem',
    'lowercase'     => 'common/bookItem',
]);

it('honours the configured transformer path', function () {
    config()->set('repository.transformer_path', app_path('Http/Transformers'));

    $this->artisan('make:transformer', ['name' => 'Book'])->assertSuccessful();

    $file = "{$this->base}/app/Http/Transformers/BookTransformer.php";

    $this->assertValidPhp($file);
    expect(file_get_contents($file))->toContain('namespace App\Http\Transformers;');
});

it('overwrites an existing transformer file', function () {
    $file = "{$this->base}/app/Transformers/BookTransformer.php";
    mkdir(dirname($file), 0755, true);
    file_put_contents($file, '<?php // custom code');

    $this->artisan('make:transformer', ['name' => 'Book'])->assertSuccessful();

    expect(file_get_contents($file))
        ->not->toContain('custom code')
        ->toContain('class BookTransformer');
});
