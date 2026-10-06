<?php

use Dashworthy\PestPluginArchIdioms\GateAbilityInspector;

function gateFixtures(): array
{
    return [__DIR__.'/Fixtures/Gate'];
}

it('passes when every permission is checked and every check names a permission', function () {
    $inspect = new GateAbilityInspector(gateFixtures());

    expect($inspect(['widgets_index', 'widgets_show', 'gadgets_index']))->toBe([]);
});

it('reports a permission nothing checks', function () {
    $inspect = new GateAbilityInspector(gateFixtures());

    expect($inspect(['widgets_index', 'widgets_show', 'gadgets_index', 'widgets_destroy']))->toBe([
        "Permission 'widgets_destroy' exists, but nothing checks it with Gate::allows(). Check it where it is needed, or remove the permission.",
    ]);
});

it('reports a checked ability no permission backs', function () {
    $inspect = new GateAbilityInspector(gateFixtures());

    expect($inspect(['widgets_index', 'widgets_show']))->toBe([
        "Gate::allows() checks the ability 'gadgets_index', but no such permission exists, so it can never be granted. Add the permission, or fix the ability name.",
    ]);
});

it('counts an ability checked at runtime as used', function () {
    $inspect = new GateAbilityInspector(gateFixtures(), ['widgets_export']);

    expect($inspect(['widgets_index', 'widgets_show', 'gadgets_index', 'widgets_export']))->toBe([]);
});
