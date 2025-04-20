# Laravel Repository Pattern Implementation

[![Latest Version on Packagist](https://img.shields.io/packagist/v/puncoz/laravel-repository.svg?style=flat-square)](https://packagist.org/packages/puncoz/laravel-repository)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/puncoz-official/laravel-repository/run-tests.yml?label=tests&branch=main)](https://github.com/puncoz-official/laravel-repository/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/puncoz-official/laravel-repository/php-cs-fixer.yml?label=code%20style&branch=main)](https://github.com/puncoz-official/laravel-repository/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/puncoz/laravel-repository.svg?style=flat-square)](https://packagist.org/packages/puncoz/laravel-repository)

A lightweight and flexible implementation of the Repository Pattern for Laravel applications. This package provides a clean and consistent way to handle data access layers in your Laravel applications.

## Features

- Clean and simple Repository Pattern implementation
- Built-in filtering system with criteria pattern
- Data transformation using Fractal
- Scope query support
- Soft delete handling
- Eloquent model integration
- Flexible and extensible architecture

## Installation

You can install the package via composer:

```bash
composer require puncoz/laravel-repository
```

You can publish the config file with:

```bash
php artisan vendor:publish --provider="JoBins\LaravelRepository\LaravelRepositoryServiceProvider"
```

## Basic Usage

1. Create a repository interface:

```php
use JoBins\LaravelRepository\Contracts\RepositoryInterface;

interface UserRepository extends RepositoryInterface
{
    // Add custom methods if needed
}
```

2. Create a repository implementation:

```php
use JoBins\LaravelRepository\LaravelRepository;
use App\Models\User;

class UserEloquentRepository extends LaravelRepository implements UserRepository
{
    public function model(): string
    {
        return User::class;
    }
}
```

3. Register the repositories in the service provider:

```php
use JoBins\LaravelRepository\LaravelRepositoryServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register()
    {
        // ideally (for larger apps) recommended to maintain these bindings in config file (bindings/repositories.php)
        // bindings/repositories.php
        // return [
        //     UserRepository::class => UserEloquentRepository::class
        // ];  
        
        collect(config('bindings.repositories'))->each(function (string $implementation, string $contract) {
            $this->app->bind($contract, $implementation);
        });
    }
}
```
In Laravel 11 and 12, manually register your service provider in bootstrap/providers.php:
```php
return [
    // Other service providers...
    App\Providers\RepositoryServiceProvider::class,
];
```

4. Use the repository in your application:

```php
class UserController extends Controller
{
    public function __construct(
        private UserRepository $repository
    ) {}

    public function index()
    {
        $users = $this->repository->all();
        
        return view('users.index', compact('users'));
    }
}
```


✅ Ready to rock!

Now, Laravel will automatically resolve the correct repository implementation via the interface wherever it's type-hinted using constructor injection or service container resolution.

## ⚙️ New Artisan Commands

This package now includes three powerful Artisan commands to help scaffold Repositories, 
Transformers, and Filters with minimal effort.

🔧 Configuration 
You can customize the default paths via the repository file located at: config/repository.php

```php
return [
    'repository_path'     => env('REPOSITORY_PATH', 'app/Repositories'),
    'interface_subfolder' => env('REPOSITORY_INTERFACE_SUBFOLDER', true),
    'repository_suffix'   => env('REPOSITORY_SUFFIX', 'EloquentRepository'),
    'interface_suffix'    => env('REPOSITORY_INTERFACE_SUFFIX', 'Repository'),
    'transformer_path'    => env('TRANSFORMER_PATH', 'app/Transformers'),
    'filter_path'         => env('FILTER_PATH', 'app/Filters'),
];
```
📦 make:repository

* interface_subfolder:

* If true, the interface will be placed in an Interfaces/ subfolder.

* If false, it will be placed in the same folder as the repository.

Generate Repository and Interface as same time with:

```bash
php artisan make:repository {name}
```

Example

```bash
php artisan make:repository product/stock 
```

This will generate:

* app/Repositories/Product/StockRepository.php

* app/Repositories/Product/Interfaces/StockRepositoryInterface.php (if interface_subfolder is true)

* Or app/Repositories/Product/StockRepositoryInterface.php (if false)

It automatically handles:

* Folder creation

* Proper naming conventions

* Namespace and use statements

🔄 make:transformer

Generate a Transformer class.
```bash
php artisan make:transformer {name}
```

Example

```bash
php artisan make:transformer product/stock
```

✅ This will generate:

* app/Transformers/Product/StockTransformer.php

🔍 make:filter

Generate a Filter class for Eloquent query filtering.

```bash
php artisan make:filter {name}
```

Example

```bash
php artisan make:filter product/stock
```

✅ This will generate:

* app/Filters/Product/StockFilter.php


🧠 💡 Notes

* Slashes (/) in names are used to create subdirectories automatically.

* All files are created with proper namespaces and boilerplate code to get you started instantly.

* You can override default paths using the config file or environment variables.

## Available Methods

### Basic Operations
- `all(array $columns = ['*'])`: Get all records
- `find(int|string $modelId, array $columns = ['*'])`: Find by ID
- `findByField(string $field, mixed $value, array $columns = ['*'])`: Find by field
- `getByField(string $field, mixed $value, array $columns = ['*'])`: Get multiple (list/collection) by field
- `create(array $data)`: Create new record
- `update(array $data, int|string $modelId)`: Update record
- `delete(int|string $modelId)`: Delete record
- `deleteWhere(array $where)`: Delete by conditions
- `updateOrCreate(array $queries, array $values = [])`: Update or create record

### Filtering and Scopes
- `filter(FilterCriteria $filter)`: Apply filter criteria
- `skipFilters(bool $status = true)`: Skip filters
- `resetFilters()`: Reset all filters
- `scopeQuery(Closure $scopeQuery)`: Add query scope

### Data Transformation
- `setTransformer(TransformerAbstract|string $transformer)`: Set transformer
- `skipTransformer(bool $skip = true)`: Skip transformation
- `present(Collection|AbstractPaginator|Model $data)`: Present data using transformer

### Relations
- `with(string|array $relations)`: Eager load relations
- `setIncludes(array|string $includes)`: Set transformer includes

## Creating Custom Filters

1. Create a filter class:

```php
use Illuminate\Database\Eloquent\Builder;
use JoBins\LaravelRepository\Filters\Filterable;

class UserFilter extends Filterable
{
    /**
     * Hook that runs before any filter methods
     * [optional method]
     */
    public function preHook(Builder $query, array $filters): Builder
    {
        return $query;
    }

    /**
     * Filter method for 'search' query/filter parameter
     * Method name must be in camelCase with 'Filter' suffix
     * Second parameter will be the value from queries/filters array (coming in the constructor)
     */
    public function searchFilter(Builder $query, ?string $search): Builder
    {
        return $query->where(function (Builder $query) use ($search) {
            $query->orWhere('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
        });
    }

    /**
     * Filter method for 'status' query/filter parameter
     */
    public function statusFilter(Builder $query, ?string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Filter method for 'created_at' query/filter parameter
     * Example of how query/filter parameter names are converted to method names:
     * 'created_at' => 'createdAtFilter'
     */
    public function createdAtFilter(Builder $query, ?string $date): Builder
    {
        return $query->whereDate('created_at', $date);
    }

    /**
     * Hook that runs after all filter methods
     */
    public function postHook(Builder $query, array $filters): Builder
    {
        return $query;
    }
}
```

2. Apply the filter with queries:

```php
// In your controller or service
$queries = [
    'search' => 'john',           // Will call searchFilter()
    'status' => 'active',         // Will call statusFilter()
    'created_at' => '2025-04-14', // Will call createdAtFilter()
];

// or,
$queries = $request->all();

$users = $repository
    ->filter(new UserFilter($queries))
    ->all();
```

### How Filters Work

1. **Query Parameter to Method Mapping**:
   - queries/filters array is passed to the constructor
   - Query/filter parameters (keys in the array passed to the constructor) are automatically mapped to filter methods
   - Method names must be in camelCase and end with 'Filter' suffix
   - Examples:
     - `search` => `searchFilter()`
     - `user_status` => `userStatusFilter()`
     - `created_at` => `createdAtFilter()`

2. **Filter Method Signature**:
   ```php
   public function someKeyFilter(Builder $query, mixed $value): Builder
   ```
   - First parameter: Eloquent Query builder instance
   - Second parameter: Value from the queries array
   - Must return: Eloquent Query Builder instance

3. **Filter Lifecycle**:
   ```
   preHook -> filterMethods -> postHook
   ```
   - `preHook`: Runs before any filter methods
   - Filter methods: Run in the order they are defined
   - `postHook`: Runs after all filter methods

4. **Optional Methods**:
   - All filter methods are optional
   - `preHook` and `postHook` are optional
   - Only methods matching query parameters will be called

## Data Transformation

The package uses [Fractal Transformers](https://fractal.thephpleague.com/transformers/) for data transformation. Here's how to implement transformers:

1. Create a transformer class:

```php
use League\Fractal\TransformerAbstract;
use App\Models\User;

class UserTransformer extends TransformerAbstract
{
    protected array $availableIncludes = [
        'posts',
        'comments'
    ];

    public function transform(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ];
    }

    public function includePosts(User $user)
    {
        return $this->collection($user->posts, new PostTransformer());
    }

    public function includeComments(User $user)
    {
        return $this->collection($user->comments, new CommentTransformer());
    }
}
```

2. Use transformation in your application:

```php
// Get transformed data
$users = $repository->all(); // Returns non-transformed data (collection)

// Set transformer to the repository
// All subsequent queries (all, find, findByField, getByField etc.) will use this transformer and return transformed data
$users = $repository->setTransformer(UserTransformer::class)->all();

// Include relations in transformation
$users = $repository
    ->setTransformer(UserTransformer::class)
    ->setIncludes(['posts', 'comments'])
    ->all();

// Skip transformer for specific query
$rawUsers = $repository
    ->skipTransformer()
    ->all(); // Returns raw data
```

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Puncoz Nepal](https://github.com/puncoz)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
