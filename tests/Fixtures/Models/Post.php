<?php

namespace JoBins\LaravelRepository\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'views' => 'integer',
    ];

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
