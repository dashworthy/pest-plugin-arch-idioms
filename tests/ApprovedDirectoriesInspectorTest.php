<?php

use Dashworthy\PestPluginArchIdioms\ApprovedDirectoriesInspector;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Action;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\BillingSettings;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\Drafts\SaveDraft;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\InvoiceTotals;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Actions\IssueInvoice;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Enums\InvoiceStatus;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\Gizmos\InvoiceGizmo;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices\InvoiceHelper;

function approvedDirectories(): ApprovedDirectoriesInspector
{
    return new ApprovedDirectoriesInspector(['Models', 'Actions' => Action::class, 'Enums' => UnitEnum::class], 'Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\*\*');
}

it('passes a class in an approved directory', function () {
    expect(approvedDirectories()(objectDescription(IssueInvoice::class)))->toBeNull()
        ->and(approvedDirectories()(objectDescription(InvoiceStatus::class)))->toBeNull();
});

it('passes a class nested deeper inside an approved directory', function () {
    expect(approvedDirectories()(objectDescription(SaveDraft::class)))->toBeNull();
});

it('reports a class in a directory that is not approved', function () {
    expect(approvedDirectories()(objectDescription(InvoiceGizmo::class)))
        ->toBe("Expecting the class to sit in an approved directory of Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices, but 'Gizmos' is not one. Use one of: Actions, Enums, Models. If none fits, ask for approval before creating a new directory or adding one to the approved list.");
});

it('reports a class sitting directly in the parent', function () {
    expect(approvedDirectories()(objectDescription(InvoiceHelper::class)))
        ->toContain('but it sits directly in Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices. Use one of: Actions, Enums, Models.');
});

it('leaves a class above the parent alone', function () {
    expect(approvedDirectories()(objectDescription(BillingSettings::class)))->toBeNull();
});

it('leaves a class outside the parent alone', function () {
    $inspect = new ApprovedDirectoriesInspector(['Actions'], 'App\Domains\*\*');

    expect($inspect(objectDescription(InvoiceGizmo::class)))->toBeNull();
});

it('matches a literal parent segment exactly', function () {
    $inspect = new ApprovedDirectoriesInspector(['Actions'], 'Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\Billing\Invoices');

    expect($inspect(objectDescription(IssueInvoice::class)))->toBeNull()
        ->and($inspect(objectDescription(InvoiceGizmo::class)))->toContain("'Gizmos' is not one");
});

it('passes a class that is the type its directory names, however deeply it is nested', function () {
    expect(approvedDirectories()(objectDescription(IssueInvoice::class)))->toBeNull()
        ->and(approvedDirectories()(objectDescription(SaveDraft::class)))->toBeNull();
});

it('reports a class that is not the type its directory names', function () {
    expect(approvedDirectories()(objectDescription(InvoiceTotals::class)))
        ->toBe('Expecting every class in the Actions directory to be a '.Action::class.', but this one is not. Extend or implement '.Action::class.', or move the class to the approved directory for what it is. If none fits, ask for approval before creating a new directory or adding one to the approved list.');
});

it('accepts any class in a directory that names no type', function () {
    $inspect = new ApprovedDirectoriesInspector(['Actions'], 'Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Domains\*\*');

    expect($inspect(objectDescription(InvoiceTotals::class)))->toBeNull();
});
