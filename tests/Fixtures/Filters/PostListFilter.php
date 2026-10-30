<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Filters;

use Illuminate\Database\Eloquent\Builder;
use JoBins\LaravelRepository\Filters\Filterable;

class PostListFilter extends Filterable
{
    /**
     * @var list<string>
     */
    public array $calls = [];

    public function preHook(Builder $query, array $filters): Builder
    {
        $this->calls[] = 'pre';

        return $query;
    }

    public function postHook(Builder $query, array $filters): Builder
    {
        $this->calls[] = 'post';

        return $query->orderBy('id');
    }

    public function titleFilter(Builder $query, mixed $value): Builder
    {
        $this->calls[] = 'title';

        return $query->where('title', 'like', "%{$value}%");
    }

    public function minViewsFilter(Builder $query, mixed $value): Builder
    {
        $this->calls[] = 'minViews';

        return $query->where('views', '>=', $value);
    }
}
