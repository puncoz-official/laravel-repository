<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use JoBins\LaravelRepository\Contracts\RepositoryInterface;
use JoBins\LaravelRepository\Exceptions\LaravelRepositoryException;
use JoBins\LaravelRepository\Tests\Fixtures\Filters\PostListFilter;
use JoBins\LaravelRepository\Tests\Fixtures\Filters\PublishedFilter;
use JoBins\LaravelRepository\Tests\Fixtures\Models\Comment;
use JoBins\LaravelRepository\Tests\Fixtures\Models\Post;
use JoBins\LaravelRepository\Tests\Fixtures\Repositories\NonModelRepository;
use JoBins\LaravelRepository\Tests\Fixtures\Repositories\PostRepository;
use JoBins\LaravelRepository\Tests\Fixtures\Repositories\UnresolvableModelRepository;
use JoBins\LaravelRepository\Tests\Fixtures\Transformers\PostTransformer;

beforeEach(function () {
    $this->createBlogSchema();

    $this->first = Post::create(['title' => 'first', 'status' => 'published', 'views' => 10]);
    $this->second = Post::create(['title' => 'second', 'status' => 'draft', 'views' => 20]);
    $this->third = Post::create(['title' => 'third', 'status' => 'published', 'views' => 30]);

    $this->repository = $this->app->make(PostRepository::class);
});

function postTitles(iterable $posts): array
{
    return collect($posts)->pluck('title')->all();
}

it('implements the repository contract', function () {
    expect($this->repository)->toBeInstanceOf(RepositoryInterface::class);
});

it('rejects a model class that is not an eloquent model', function () {
    $this->app->make(NonModelRepository::class);
})->throws(LaravelRepositoryException::class, 'must be an instance of Illuminate\Database\Eloquent\Model');

it('rejects a model class that cannot be resolved', function () {
    $this->app->make(UnresolvableModelRepository::class);
})->throws(LaravelRepositoryException::class, 'Unable to resolve model');

it('returns all records', function () {
    $posts = $this->repository->all();

    expect($posts)->toBeInstanceOf(Collection::class)
        ->and(postTitles($posts))->toBe(['first', 'second', 'third']);
});

it('returns only the requested columns', function () {
    $post = $this->repository->all(['id'])->first();

    expect(array_keys($post->getAttributes()))->toBe(['id'])
        ->and($post->getKey())->toEqual($this->first->id);
});

it('paginates using the configured default limit', function () {
    config()->set('repository.pagination.limit', 2);

    $page = $this->repository->paginate();

    expect($page)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($page->perPage())->toBe(2)
        ->and($page->total())->toBe(3);
});

it('paginates using an explicit limit', function () {
    expect($this->repository->paginate(1)->perPage())->toBe(1);
});

it('finds a record by key', function () {
    expect($this->repository->find($this->second->id)->title)->toBe('second');
});

it('fails when a record cannot be found', function () {
    $this->repository->find(999);
})->throws(ModelNotFoundException::class);

it('finds the first record matching a field', function () {
    expect($this->repository->findByField('status', 'published')->title)->toBe('first');
});

it('fails when no record matches a field', function () {
    $this->repository->findByField('status', 'archived');
})->throws(ModelNotFoundException::class);

it('gets records by field', function () {
    expect(postTitles($this->repository->getByField('status', 'published')))->toBe(['first', 'third']);
});

it('gets records matching conditions', function () {
    $posts = $this->repository->getWhere([['status', '=', 'published'], ['views', '>', 10]]);

    expect(postTitles($posts))->toBe(['third']);
});

it('gets records where a field is in a list', function () {
    expect(postTitles($this->repository->getWhereIn('views', [10, 30])))->toBe(['first', 'third']);
});

it('gets records where a field is not in a list', function () {
    expect(postTitles($this->repository->getWhereNotIn('views', [10, 30])))->toBe(['second']);
});

it('gets records where a field is between two values', function () {
    expect(postTitles($this->repository->getWhereBetween('views', [15, 35])))->toBe(['second', 'third']);
});

it('orders results', function () {
    expect(postTitles($this->repository->orderBy('views', 'desc')->all()))->toBe(['third', 'second', 'first']);
});

it('eager loads relations', function () {
    Comment::create(['post_id' => $this->first->id, 'body' => 'nice']);

    $post = $this->repository->with('comments')->find($this->first->id);

    expect($post->relationLoaded('comments'))->toBeTrue()
        ->and($post->comments->pluck('body')->all())->toBe(['nice']);
});

it('resets the query after each call', function () {
    $this->repository->orderBy('views', 'desc')->all();

    expect(postTitles($this->repository->all()))->toBe(['first', 'second', 'third']);
});

it('exposes the underlying builder with filters and scope applied', function () {
    $builder = $this->repository
        ->filter(new PublishedFilter())
        ->scopeQuery(fn (Builder $query) => $query->where('views', '>', 10))
        ->getBuilder();

    expect($builder)->toBeInstanceOf(Builder::class)
        ->and(postTitles($builder->get()))->toBe(['third']);
});

it('creates a record', function () {
    $post = $this->repository->create(['title' => 'fourth']);

    expect($post)->toBeInstanceOf(Post::class)
        ->and($post->exists)->toBeTrue()
        ->and(Post::where('title', 'fourth')->exists())->toBeTrue();
});

it('updates a record', function () {
    $post = $this->repository->update(['title' => 'updated'], $this->first->id);

    expect($post->title)->toBe('updated')
        ->and($this->first->fresh()->title)->toBe('updated');
});

it('fails to update a missing record', function () {
    $this->repository->update(['title' => 'updated'], 999);
})->throws(ModelNotFoundException::class);

it('updates an existing record or creates a new one', function () {
    $this->repository->updateOrCreate(['title' => 'first'], ['views' => 99]);
    $this->repository->updateOrCreate(['title' => 'fourth'], ['views' => 1]);

    expect($this->first->fresh()->views)->toBe(99)
        ->and(Post::where('title', 'fourth')->value('views'))->toBe(1);
});

it('deletes a record by key', function () {
    $this->repository->delete($this->first->id);

    expect($this->first->fresh()->trashed())->toBeTrue();
});

it('deletes records matching conditions', function () {
    $this->repository->deleteWhere(['status' => 'published']);

    expect(postTitles(Post::all()))->toBe(['second']);
});

it('deletes records where a column is in a list', function () {
    $this->repository->deleteWhereIn('views', [10, 20]);

    expect(postTitles(Post::all()))->toBe(['third']);
});

it('deletes records where a column is not in a list', function () {
    $this->repository->deleteWhereNotIn('views', [10, 20]);

    expect(postTitles(Post::all()))->toBe(['first', 'second']);
});

it('applies filter criteria', function () {
    expect(postTitles($this->repository->filter(new PublishedFilter())->all()))->toBe(['first', 'third']);
});

it('keeps filters across calls until they are reset', function () {
    $this->repository->filter(new PublishedFilter());

    expect(postTitles($this->repository->all()))->toBe(['first', 'third'])
        ->and(postTitles($this->repository->all()))->toBe(['first', 'third'])
        ->and(postTitles($this->repository->resetFilters()->all()))->toBe(['first', 'second', 'third']);
});

it('skips filters when asked', function () {
    $this->repository->filter(new PublishedFilter())->skipFilters();

    expect(postTitles($this->repository->all()))->toBe(['first', 'second', 'third'])
        ->and(postTitles($this->repository->skipFilters(false)->all()))->toBe(['first', 'third']);
});

it('lists the registered filters', function () {
    $filter = new PublishedFilter();

    expect($this->repository->filter($filter)->getFilters()->all())->toBe([$filter]);
});

it('removes filters by class name', function () {
    $listFilter = new PostListFilter(['title' => 'ir']);

    $this->repository->filter(new PublishedFilter())->filter($listFilter);
    $this->repository->removeFilter(PublishedFilter::class);

    expect($this->repository->getFilters()->values()->all())->toBe([$listFilter])
        ->and(postTitles($this->repository->all()))->toBe(['first', 'third']);
});

it('dispatches filterable values to camel cased filter methods between hooks', function () {
    $filter = new PostListFilter(['title' => 'i', 'min_views' => 15, 'unknown' => 'ignored']);

    $posts = $this->repository->filter($filter)->all();

    expect(postTitles($posts))->toBe(['third'])
        ->and($filter->calls)->toBe(['pre', 'title', 'minViews', 'post']);
});

it('applies a scope query to the next call only', function () {
    $scoped = $this->repository->scopeQuery(fn (Builder $query) => $query->where('views', '>', 15))->all();

    expect(postTitles($scoped))->toBe(['second', 'third'])
        ->and(postTitles($this->repository->all()))->toBe(['first', 'second', 'third']);
});

it('includes soft deleted records when asked', function () {
    $this->first->delete();

    expect(postTitles($this->repository->all()))->toBe(['second', 'third'])
        ->and(postTitles($this->repository->withTrashed()->all()))->toBe(['first', 'second', 'third']);
});

it('returns only soft deleted records when asked', function () {
    $this->first->delete();

    expect(postTitles($this->repository->onlyTrashed()->all()))->toBe(['first']);
});

it('transforms a single record', function () {
    $post = $this->repository->setTransformer(new PostTransformer())->find($this->first->id);

    expect($post)->toBe(['id' => $this->first->id, 'title' => 'FIRST']);
});

it('resolves a transformer from its class name', function () {
    $posts = $this->repository->setTransformer(PostTransformer::class)->getByField('status', 'draft');

    expect($posts)->toBe([['id' => $this->second->id, 'title' => 'SECOND']]);
});

it('transforms a paginator and adds pagination meta', function () {
    $page = $this->repository->setTransformer(new PostTransformer())->paginate(2);

    expect(collect($page)->except('meta')->all())->toBe([
        ['id' => $this->first->id, 'title' => 'FIRST'],
        ['id' => $this->second->id, 'title' => 'SECOND'],
    ])
        ->and($page['meta']['pagination'])->toMatchArray([
            'total' => 3,
            'count' => 2,
            'per_page' => 2,
            'current_page' => 1,
            'total_pages' => 2,
        ]);
});

it('adds requested includes', function () {
    Comment::create(['post_id' => $this->first->id, 'body' => 'nice']);

    $post = $this->repository
        ->setTransformer(new PostTransformer())
        ->setIncludes('comments')
        ->find($this->first->id);

    expect($post)->toBe([
        'id' => $this->first->id,
        'title' => 'FIRST',
        'comments' => [['body' => 'nice']],
    ]);
});

it('accepts includes as an array', function () {
    $post = $this->repository
        ->setTransformer(new PostTransformer())
        ->setIncludes(['comments'])
        ->find($this->first->id);

    expect($post)->toHaveKey('comments');
});

it('returns models when the transformer is skipped', function () {
    $post = $this->repository->setTransformer(new PostTransformer())->skipTransformer()->find($this->first->id);

    expect($post)->toBeInstanceOf(Post::class);
});

it('returns models after the transformer is reset', function () {
    $post = $this->repository->setTransformer(new PostTransformer())->resetTransformer()->find($this->first->id);

    expect($post)->toBeInstanceOf(Post::class);
});

it('transforms the record returned by update', function () {
    $post = $this->repository->setTransformer(new PostTransformer())->update(['title' => 'new'], $this->first->id);

    expect($post)->toBe(['id' => $this->first->id, 'title' => 'NEW']);
});

it('does not transform delete operations', function () {
    $this->repository->setTransformer(new PostTransformer())->delete($this->first->id);

    expect($this->first->fresh()->trashed())->toBeTrue();
});
