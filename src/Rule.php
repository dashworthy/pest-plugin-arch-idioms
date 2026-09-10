<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms;

use Closure;
use Pest\Arch\Blueprint;
use Pest\Arch\Collections\Dependencies;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\Exceptions\ArchExpectationFailedException;
use Pest\Arch\Options\LayerOptions;
use Pest\Arch\SingleArchExpectation;
use Pest\Arch\Support\FileLineFinder;
use Pest\Arch\ValueObjects\Targets;
use Pest\Arch\ValueObjects\Violation;
use Pest\Expectation;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Builds an arch expectation whose failure message can name the offending
 * value.
 *
 * Pest's own Targeted::make captures its message as a string before iteration
 * begins, so a message can only describe the rule, never the value that broke
 * it. Blueprint::targeted() invokes predicate, then line finder, then failure —
 * in that order, for each object — so a variable captured by reference in the
 * predicate is readable by the two callbacks that follow it.
 */
final class Rule
{
    /**
     * @param  Expectation<array<int, string>|string>  $expectation
     * @param  Closure(ObjectDescription, ?string): bool  $predicate
     * @param  Closure(Violation, ?string): string  $message
     * @param  (callable(string): int)|null  $line
     */
    public static function make(
        Expectation $expectation,
        Closure $predicate,
        Closure $message,
        ?callable $line = null,
    ): ArchExpectation {
        $detail = null;

        $blueprint = Blueprint::make(
            Targets::fromExpectation($expectation),
            Dependencies::fromExpectationInput([]),
        );

        $lineFinder = $line ?? FileLineFinder::where(
            fn (string $candidate): bool => str_contains($candidate, 'class'),
        );

        return SingleArchExpectation::fromExpectation(
            $expectation,
            function (LayerOptions $options) use ($blueprint, $predicate, $message, $lineFinder, &$detail): void {
                $blueprint->targeted(
                    function (ObjectDescription $object) use ($predicate, &$detail): bool {
                        $detail = null;

                        return $predicate($object, $detail);
                    },
                    $options,
                    function (Violation $violation) use ($message, &$detail): never {
                        throw new ArchExpectationFailedException(
                            $violation,
                            $message($violation, $detail),
                        );
                    },
                    $lineFinder,
                );
            },
        );
    }
}
