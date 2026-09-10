<?php

use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent\ConventionalModel;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent\FillableModel;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent\GuardedModel;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent\WideOpenModel;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\AssertionFailedError;

it('accepts a model with an explicit guard list', function () {
    expect(GuardedModel::class)->toGuardMassAssignment();
});

it('accepts a model with an explicit fillable list', function () {
    expect(FillableModel::class)->toGuardMassAssignment();
});

it('accepts a model relying on the fully guarded default', function () {
    expect(ConventionalModel::class)->toGuardMassAssignment();
});

it('rejects a model that unguards everything', function () {
    expect(fn () => expect(WideOpenModel::class)->toGuardMassAssignment()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares an empty $guarded with no $fillable allow-list');
});

it('accepts a whole namespace of models once the unguarded one is excluded', function () {
    expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent')
        ->classes()
        ->extending(Model::class)
        ->toGuardMassAssignment()
        ->ignoring(WideOpenModel::class);
});

it('rejects the same namespace without the exclusion, proving it is load-bearing', function () {
    expect(fn () => expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent')
        ->classes()
        ->extending(Model::class)
        ->toGuardMassAssignment()
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares an empty $guarded with no $fillable allow-list');
});

it('sweeps a non-model into the selection when the type filter is dropped', function () {
    expect(fn () => expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent')
        ->classes()
        ->toGuardMassAssignment()
        ->ignoring(WideOpenModel::class)
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'is not one');
});
