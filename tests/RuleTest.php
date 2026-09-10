<?php

use Dashworthy\PestPluginArchIdioms\Rule;
use Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Support\AlwaysFails;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use Pest\Arch\Support\FileLineFinder;
use PHPUnit\Architecture\Elements\ObjectDescription;
use PHPUnit\Framework\AssertionFailedError;

it('passes when the predicate is satisfied', function () {
    // Verified here rather than left to teardown. Without the explicit call the
    // lazy expectation still resolves — after the test body has finished — so
    // throwsNoExceptions() declared the test assertion-free while one assertion
    // was performed, and the run was reported risky rather than passing.
    Rule::make(
        expect(AlwaysFails::class),
        fn (ObjectDescription $object, ?string &$detail): bool => true,
        fn (): string => 'unreachable',
    )->ensureLazyExpectationIsVerified();

    expect(true)->toBeTrue();
});

it('reports the detail the predicate recorded', function () {
    expect(function (): void {
        Rule::make(
            expect(AlwaysFails::class),
            function (ObjectDescription $object, ?string &$detail): bool {
                $detail = 'find-me';

                return false;
            },
            fn ($violation, ?string $detail): string => "offending value was '{$detail}'",
        )->ensureLazyExpectationIsVerified();
    })->toThrow(AssertionFailedError::class, "offending value was 'find-me'");
});

it('points the violation at a line chosen by the line finder', function () {
    $captured = null;

    try {
        Rule::make(
            expect(AlwaysFails::class),
            function (ObjectDescription $object, ?string &$detail): bool {
                $detail = 'find-me';

                return false;
            },
            fn ($violation, ?string $detail): string => 'failed',
            FileLineFinder::where(fn (string $line): bool => str_contains($line, 'find-me')),
        )->ensureLazyExpectationIsVerified();
    } catch (ArchExpectationFailedException $e) {
        $captured = $e;
    }

    expect($captured)->not->toBeNull()
        ->and($captured->toCollisionEditor()->getLine())->toBe(9);
});
