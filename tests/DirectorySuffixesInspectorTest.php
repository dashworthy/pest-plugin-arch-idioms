<?php

use Dashworthy\PestPluginArchIdioms\DirectorySuffixesInspector;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\BillingSettings;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\Drafts\SaveDraft;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\InvoiceTotals;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\IssueInvoice;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Enums\InvoiceStatus;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Gizmos\InvoiceGizmo;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\InvoiceHelper;

function directorySuffixes(): DirectorySuffixesInspector
{
    return new DirectorySuffixesInspector(['Actions' => 'Invoice', 'Enums' => 'Status'], 'Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\*\*');
}

it('passes a class that ends in its directory\'s suffix', function () {
    expect(directorySuffixes()(objectDescription(IssueInvoice::class)))->toBeNull()
        ->and(directorySuffixes()(objectDescription(InvoiceStatus::class)))->toBeNull();
});

it('reports a class that does not end in its directory\'s suffix', function () {
    expect(directorySuffixes()(objectDescription(InvoiceTotals::class)))
        ->toBe("Expecting every class in the Actions directory to end in 'Invoice', but InvoiceTotals does not. Rename it to end in 'Invoice', or move it to the directory for what it is.");
});

it('holds a class nested deeper inside a directory to the same suffix', function () {
    expect(directorySuffixes()(objectDescription(SaveDraft::class)))->toContain('but SaveDraft does not');
});

it('leaves a class in a directory with no suffix alone', function () {
    expect(directorySuffixes()(objectDescription(InvoiceGizmo::class)))->toBeNull();
});

it('leaves a class directly in a module, above it or outside it alone', function () {
    $outside = new DirectorySuffixesInspector(['Actions' => 'Action'], 'App\Domains\*\*');

    expect(directorySuffixes()(objectDescription(InvoiceHelper::class)))->toBeNull()
        ->and(directorySuffixes()(objectDescription(BillingSettings::class)))->toBeNull()
        ->and($outside(objectDescription(InvoiceTotals::class)))->toBeNull();
});
