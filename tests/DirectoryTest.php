<?php

use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Action;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\InvoiceTotals;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\IssueInvoice;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Gizmos\InvoiceGizmo;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\InvoiceHelper;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use PHPUnit\Framework\AssertionFailedError;

const FIXTURE_MODULES = 'Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\*\*';

describe('toUseApprovedDirectories', function () {
    it('accepts a class in an approved directory', function () {
        expect(IssueInvoice::class)->toUseApprovedDirectories(['Actions', 'Enums'], beneath: FIXTURE_MODULES);
    });

    it('accepts a whole namespace once the classes that break the rule are ignored', function () {
        expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains')
            ->toUseApprovedDirectories(['Actions', 'Enums'], beneath: FIXTURE_MODULES)
            ->ignoring([InvoiceGizmo::class, InvoiceHelper::class]);
    });

    it('accepts every class in a typed directory once the one that is not that type is ignored', function () {
        expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains')
            ->toUseApprovedDirectories(['Actions' => Action::class, 'Enums' => UnitEnum::class], beneath: FIXTURE_MODULES)
            ->ignoring([InvoiceGizmo::class, InvoiceHelper::class, InvoiceTotals::class]);
    });

    it('rejects a class in a directory that is not approved, and says to ask before adding one', function () {
        expect(fn () => expect(InvoiceGizmo::class)
            ->toUseApprovedDirectories(['Actions', 'Enums'], beneath: FIXTURE_MODULES)
            ->ensureLazyExpectationIsVerified())
            ->toThrow(function (AssertionFailedError $e): void {
                expect($e->getMessage())
                    ->toContain("'Gizmos' is not one. Use one of: Actions, Enums.")
                    ->toContain('ask for approval before creating a new directory or adding one to the approved list');
            });
    });

    it('rejects a class that is not the type its directory names', function () {
        expect(fn () => expect(InvoiceTotals::class)
            ->toUseApprovedDirectories(['Actions' => Action::class], beneath: FIXTURE_MODULES)
            ->ensureLazyExpectationIsVerified())
            ->toThrow(AssertionFailedError::class, 'Expecting every class in the Actions directory to be a '.Action::class);
    });

    it('rejects a class sitting directly in a module', function () {
        expect(fn () => expect(InvoiceHelper::class)
            ->toUseApprovedDirectories(['Actions', 'Enums'], beneath: FIXTURE_MODULES)
            ->ensureLazyExpectationIsVerified())
            ->toThrow(AssertionFailedError::class, 'but it sits directly in Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices');
    });

    it('points the violation at the class declaration', function () {
        $captured = null;

        try {
            expect(InvoiceGizmo::class)
                ->toUseApprovedDirectories(['Actions'], beneath: FIXTURE_MODULES)
                ->ensureLazyExpectationIsVerified();
        } catch (ArchExpectationFailedException $e) {
            $captured = $e;
        }

        expect($captured)->not->toBeNull()
            ->and($captured->toCollisionEditor()->getLine())->toBe(5);
    });
});
