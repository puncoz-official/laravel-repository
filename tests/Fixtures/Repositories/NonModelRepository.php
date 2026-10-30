<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Repositories;

use JoBins\LaravelRepository\LaravelRepository;
use stdClass;

class NonModelRepository extends LaravelRepository
{
    public function model(): string
    {
        return stdClass::class;
    }
}
