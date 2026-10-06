<?php

use Dashworthy\PestPluginArchIdioms\GateAbilities;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\StoreWidgetRequest;

it('reads the abilities checked with a string literal', function () {
    expect(GateAbilities::inCode("Gate::allows('widgets_index'); Gate::allows( \"widgets_show\" );"))
        ->toBe(['widgets_index', 'widgets_show']);
});

it('ignores an ability built at runtime', function () {
    expect(GateAbilities::inCode('Gate::allows($ability); Gate::allows(Permission::INDEX);'))->toBe([]);
});

it('lists an ability checked twice once', function () {
    expect(GateAbilities::inCode("Gate::allows('widgets_index') || Gate::allows('widgets_index')"))->toBe(['widgets_index']);
});

it('reads only the body of the given method', function () {
    expect(GateAbilities::inMethod(new ReflectionMethod(StoreWidgetRequest::class, 'authorize')))->toBe(['widgets_store']);
});

it('reads every php file beneath the directories', function () {
    $directory = __DIR__.'/Fixtures/Gate';

    expect(GateAbilities::inDirectories([$directory]))->toEqualCanonicalizing(['widgets_index', 'widgets_show', 'gadgets_index']);
});
