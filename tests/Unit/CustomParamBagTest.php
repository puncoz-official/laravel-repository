<?php

use JoBins\LaravelRepository\Fractal\CustomParamBag;

beforeEach(function () {
    $this->bag = new CustomParamBag(['limit' => [10, 5], 'order' => 'desc']);
});

it('reads params through get, property and array access', function () {
    expect($this->bag->get('order'))->toBe('desc')
        ->and($this->bag->order)->toBe('desc')
        ->and($this->bag['limit'])->toBe([10, 5]);
});

it('returns null for missing params', function () {
    expect($this->bag->get('missing'))->toBeNull()
        ->and($this->bag->missing)->toBeNull()
        ->and($this->bag['missing'])->toBeNull();
});

it('reports whether a param exists', function () {
    expect(isset($this->bag->order))->toBeTrue()
        ->and(isset($this->bag['limit']))->toBeTrue()
        ->and(isset($this->bag->missing))->toBeFalse()
        ->and(isset($this->bag['missing']))->toBeFalse();
});

it('iterates over its params', function () {
    expect(iterator_to_array($this->bag))->toBe(['limit' => [10, 5], 'order' => 'desc']);
});

it('rejects setting a param as a property', function () {
    $this->bag->order = 'asc';
})->throws(LogicException::class, 'Modifying parameters is not permitted');

it('rejects unsetting a param as a property', function () {
    unset($this->bag->order);
})->throws(LogicException::class, 'Modifying parameters is not permitted');

it('rejects setting a param through array access', function () {
    $this->bag['order'] = 'asc';
})->throws(LogicException::class, 'Modifying parameters is not permitted');

it('rejects unsetting a param through array access', function () {
    unset($this->bag['order']);
})->throws(LogicException::class, 'Modifying parameters is not permitted');
