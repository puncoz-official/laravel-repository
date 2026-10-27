<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Repositories;

use Illuminate\Contracts\Database\Eloquent\Builder;
use JoBins\LaravelRepository\LaravelRepository;
use JoBins\LaravelRepository\Tests\Fixtures\Models\Post;

class ContractBuilderPostRepository extends LaravelRepository
{
    protected Builder $model;

    public function model(): string
    {
        return Post::class;
    }
}
