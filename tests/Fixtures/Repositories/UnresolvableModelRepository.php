<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Repositories;

use JoBins\LaravelRepository\LaravelRepository;

class UnresolvableModelRepository extends LaravelRepository
{
    public function model(): string
    {
        return 'JoBins\LaravelRepository\Tests\Fixtures\Models\Missing';
    }
}
