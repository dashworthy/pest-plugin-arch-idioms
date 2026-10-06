<?php

use Dashworthy\PestPluginArchIdioms\GateAuthorizationInspector;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\AbstractWidgetRequest;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\NoAuthorizeRequest;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\StoreWidgetRequest;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\UntypedRequest;

it('passes a request that checks a permission', function () {
    expect((new GateAuthorizationInspector)(objectDescription(StoreWidgetRequest::class)))->toBeNull();
});

it('passes a request that checks the permission its name implies', function () {
    $inspect = new GateAuthorizationInspector(fn (string $request): string => 'widgets_store');

    expect($inspect(objectDescription(StoreWidgetRequest::class)))->toBeNull();
});

it('reports a request that checks a different permission than its name implies', function () {
    $inspect = new GateAuthorizationInspector(fn (string $request): string => 'widgets_update');

    expect($inspect(objectDescription(StoreWidgetRequest::class)))
        ->toBe("Expecting authorize() to check the 'widgets_update' permission this request's name implies, but it checks 'widgets_store'. Rename the permission or the request so they agree.");
});

it('accepts any permission when the convention has no opinion', function () {
    $inspect = new GateAuthorizationInspector(fn (string $request): ?string => null);

    expect($inspect(objectDescription(StoreWidgetRequest::class)))->toBeNull();
});

it('reports a request whose authorize() checks no permission', function () {
    expect((new GateAuthorizationInspector)(objectDescription(UntypedRequest::class)))
        ->toContain('authorize() does not call Gate::allows()');
});

it('reports a request with no authorize() method', function () {
    expect((new GateAuthorizationInspector)(objectDescription(NoAuthorizeRequest::class)))
        ->toContain('declares no authorize() method');
});

it('skips an abstract request', function () {
    expect((new GateAuthorizationInspector)(objectDescription(AbstractWidgetRequest::class)))->toBeNull();
});
