<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Filters;

use Illuminate\Database\Eloquent\Builder;
use JoBins\LaravelRepository\Contracts\FilterCriteria;

class PublishedFilter implements FilterCriteria
{
    public function apply(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }
}
