<?php

use Pest\Arch\Blueprint;
use Pest\Arch\SingleArchExpectation;
use Pest\Arch\Support\FileLineFinder;
use PhpParser\NodeVisitor\NameResolver;
use PHPUnit\Architecture\Elements\ObjectDescription;
use PHPUnit\Architecture\Services\ServiceContainer;

it('still exposes the object description shape this package reads', function () {
    expect(property_exists(ObjectDescription::class, 'stmts'))->toBeTrue()
        ->and(property_exists(ObjectDescription::class, 'reflectionClass'))->toBeTrue()
        ->and(property_exists(ObjectDescription::class, 'name'))->toBeTrue()
        ->and(property_exists(ObjectDescription::class, 'path'))->toBeTrue();
});

it('still exposes the blueprint and expectation entry points', function () {
    expect(method_exists(Blueprint::class, 'targeted'))->toBeTrue()
        ->and(method_exists(Blueprint::class, 'make'))->toBeTrue()
        ->and(method_exists(SingleArchExpectation::class, 'fromExpectation'))->toBeTrue()
        ->and(method_exists(FileLineFinder::class, 'where'))->toBeTrue();
});

it('still resolves names before handing us an ast', function () {
    ServiceContainer::init();

    $visitors = (fn (): array => $this->visitors)->call(ServiceContainer::$nodeTraverser);

    expect($visitors)->toContainOnlyInstancesOf(NameResolver::class);
});
