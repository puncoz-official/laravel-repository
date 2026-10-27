<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Repositories;

use JoBins\LaravelRepository\LaravelRepository;
use JoBins\LaravelRepository\Tests\Fixtures\Models\Post;

class PostRepository extends LaravelRepository
{
    public function model(): string
    {
        return Post::class;
    }
}
