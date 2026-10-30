<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Repositories;

use JoBins\LaravelRepository\LaravelRepository;
use JoBins\LaravelRepository\Tests\Fixtures\Models\Comment;

class CommentRepository extends LaravelRepository
{
    public function model(): string
    {
        return Comment::class;
    }
}
