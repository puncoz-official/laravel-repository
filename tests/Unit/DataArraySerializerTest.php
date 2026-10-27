<?php

use JoBins\LaravelRepository\Serializer\DataArraySerializer;

it('wraps a collection in its resource key', function () {
    expect((new DataArraySerializer())->collection('posts', [['id' => 1]]))->toBe(['posts' => [['id' => 1]]]);
});

it('returns a collection unwrapped without a resource key', function (?string $key) {
    expect((new DataArraySerializer())->collection($key, [['id' => 1]]))->toBe([['id' => 1]]);
})->with([null, '']);

it('never wraps an item', function () {
    expect((new DataArraySerializer())->item('post', ['id' => 1]))->toBe(['id' => 1]);
});

it('serializes a null resource as an empty array', function () {
    expect((new DataArraySerializer())->null())->toBe([]);
});
