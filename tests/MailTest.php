<?php

use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Mail\MarkdownMailable;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Mail\NoContentMailable;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Mail\PlainViewMailable;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use PHPUnit\Framework\AssertionFailedError;

it('accepts a mailable built from a markdown template', function () {
    expect(MarkdownMailable::class)->toUseMarkdownMailTemplates();
});

it('rejects a mailable built from a plain view', function () {
    expect(fn () => expect(PlainViewMailable::class)->toUseMarkdownMailTemplates()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'does not name a markdown template');
});

it('rejects a mailable with no content method', function () {
    expect(fn () => expect(NoContentMailable::class)->toUseMarkdownMailTemplates()->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'declares no content() method');
});

it('accepts a whole namespace of mailables once the non-markdown ones are excluded', function () {
    expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Mail')
        ->classes()
        ->toUseMarkdownMailTemplates()
        ->ignoring([PlainViewMailable::class, NoContentMailable::class]);
});

it('rejects the same namespace without the exclusions, proving they are load-bearing', function () {
    expect(fn () => expect('Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Mail')
        ->classes()
        ->toUseMarkdownMailTemplates()
        ->ensureLazyExpectationIsVerified())
        ->toThrow(AssertionFailedError::class, 'Expecting the mailable to render from a markdown template');
});

it('points a missing content() method at a non-zero line', function () {
    $captured = null;

    try {
        expect(NoContentMailable::class)->toUseMarkdownMailTemplates()->ensureLazyExpectationIsVerified();
    } catch (ArchExpectationFailedException $e) {
        $captured = $e;
    }

    expect($captured)->not->toBeNull()
        ->and($captured->toCollisionEditor()->getLine())->not->toBe(0);
});
