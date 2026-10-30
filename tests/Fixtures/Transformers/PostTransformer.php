<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Transformers;

use JoBins\LaravelRepository\Tests\Fixtures\Models\Comment;
use JoBins\LaravelRepository\Tests\Fixtures\Models\Post;
use League\Fractal\Resource\Collection;
use League\Fractal\TransformerAbstract;

class PostTransformer extends TransformerAbstract
{
    protected array $availableIncludes = ['comments'];

    /**
     * @return array<string, mixed>
     */
    public function transform(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => strtoupper($post->title),
        ];
    }

    public function includeComments(Post $post): Collection
    {
        return $this->collection($post->comments, fn (Comment $comment) => ['body' => $comment->body]);
    }
}
