<?php

use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\NoAuthorizeRequest;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\StoreWidgetRequest;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests\UntypedRequest;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use PHPUnit\Framework\AssertionFailedError;

describe('toAuthorizeWithGate', function () {
    it('accepts a request whose authorize() checks a permission', function () {
        expect(StoreWidgetRequest::class)->toAuthorizeWithGate();
    });

    it('accepts a request checking the permission the convention derives from its name', function () {
        expect(StoreWidgetRequest::class)->toAuthorizeWithGate(fn (string $request): string => 'widgets_store');
    });

    it('rejects a request checking a different permission than the convention derives', function () {
        expect(fn () => expect(StoreWidgetRequest::class)
            ->toAuthorizeWithGate(fn (string $request): string => 'widgets_update')
            ->ensureLazyExpectationIsVerified())
            ->toThrow(AssertionFailedError::class, "check the 'widgets_update' permission this request's name implies, but it checks 'widgets_store'");
    });

    it('rejects a request whose authorize() checks no permission', function () {
        expect(fn () => expect(UntypedRequest::class)->toAuthorizeWithGate()->ensureLazyExpectationIsVerified())
            ->toThrow(AssertionFailedError::class, 'authorize() does not call Gate::allows()');
    });

    it('rejects a request with no authorize() method', function () {
        expect(fn () => expect(NoAuthorizeRequest::class)->toAuthorizeWithGate()->ensureLazyExpectationIsVerified())
            ->toThrow(AssertionFailedError::class, 'declares no authorize() method');
    });

    it('points a missing authorize() method at a non-zero line', function () {
        $captured = null;

        try {
            expect(NoAuthorizeRequest::class)->toAuthorizeWithGate()->ensureLazyExpectationIsVerified();
        } catch (ArchExpectationFailedException $e) {
            $captured = $e;
        }

        expect($captured)->not->toBeNull()
            ->and($captured->toCollisionEditor()->getLine())->not->toBe(0);
    });
});

describe('toMatchGateAbilities', function () {
    it('accepts permissions that match the abilities the code checks', function () {
        expect(['widgets_index', 'widgets_show', 'gadgets_index'])
            ->toMatchGateAbilities([__DIR__.'/Fixtures/Gate']);
    });

    it('accepts an ability checked at runtime once it is declared', function () {
        expect(['widgets_index', 'widgets_show', 'gadgets_index', 'widgets_export'])
            ->toMatchGateAbilities([__DIR__.'/Fixtures/Gate'], alsoChecked: ['widgets_export']);
    });

    it('reports every mismatch in one run', function () {
        expect(fn () => expect(['widgets_index', 'widgets_destroy'])->toMatchGateAbilities([__DIR__.'/Fixtures/Gate']))
            ->toThrow(function (AssertionFailedError $e): void {
                expect($e->getMessage())
                    ->toContain("Permission 'widgets_destroy' exists, but nothing checks it")
                    ->toContain("checks the ability 'widgets_show', but no such permission exists")
                    ->toContain("checks the ability 'gadgets_index', but no such permission exists");
            });
    });

    it('chains like any other expectation', function () {
        expect(['widgets_index', 'widgets_show', 'gadgets_index'])
            ->toMatchGateAbilities([__DIR__.'/Fixtures/Gate'])
            ->toHaveCount(3);
    });
});
