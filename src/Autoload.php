<?php

declare(strict_types=1);

use Dashworthy\PestPluginArchIdioms\Rule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Pest\Arch\Contracts\ArchExpectation;
use Pest\Arch\Support\FileLineFinder;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Return_;
use PhpParser\NodeFinder;
use PHPUnit\Architecture\Elements\ObjectDescription;

/*
 | Each closure MUST declare ": ArchExpectation" as its return type.
 | Pest\Expectation::__call inspects that exact return type to decide whether to
 | invoke the closure directly; without it the call is routed through
 | ExpectationPipeline and the lazy arch evaluation never runs.
 */

/*
 | FileLineFinder::where() returns 0 when nothing in the file matches, which
 | points a violation at line 0 — worse than Rule::make's own "class" default.
 | This composes a fallback: try the method signature first, and if the
 | method itself is missing (the line finder's search string never matches),
 | fall back to the line declaring the class.
 */
$methodOrClassLine = static fn (string $needle): callable => static function (string $path) use ($needle): int {
    $line = FileLineFinder::where(fn (string $candidate): bool => str_contains($candidate, $needle))($path);

    return $line !== 0
        ? $line
        : FileLineFinder::where(fn (string $candidate): bool => str_contains($candidate, 'class'))($path);
};

expect()->extend('toBeQueued', function (): ArchExpectation {
    return Rule::make(
        $this,
        fn (ObjectDescription $object, ?string &$detail): bool => $object->reflectionClass->implementsInterface(ShouldQueue::class),
        fn (): string => sprintf(
            'Expecting the class to be dispatched asynchronously, but it does not implement %s. Implement it, or assert the decision with toBeSync().',
            ShouldQueue::class,
        ),
    );
});

expect()->extend('toBeQueuedAfterCommit', function (): ArchExpectation {
    return Rule::make(
        $this,
        fn (ObjectDescription $object, ?string &$detail): bool => $object->reflectionClass->implementsInterface(ShouldQueueAfterCommit::class),
        fn (): string => sprintf(
            'Expecting the class to wait for the transaction to commit, but it does not implement %s.',
            ShouldQueueAfterCommit::class,
        ),
    );
});

expect()->extend('toBeSync', function (): ArchExpectation {
    return Rule::make(
        $this,
        fn (ObjectDescription $object, ?string &$detail): bool => ! $object->reflectionClass->implementsInterface(ShouldQueue::class),
        fn (): string => 'Expecting the class to be deliberately synchronous, but it is queued.',
    );
});

expect()->extend('toMatchTableName', function (): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            if (! $object->reflectionClass->isSubclassOf(Model::class)) {
                $detail = 'not-a-model';

                return false;
            }

            if ($object->reflectionClass->isAbstract()) {
                return true;
            }

            /** @var Model $model */
            $model = $object->reflectionClass->newInstanceWithoutConstructor();

            $expected = Str::snake(Str::pluralStudly(class_basename($object->name)));
            $actual = $model->getTable();

            $detail = sprintf('%s|%s', $actual, $expected);

            return $actual === $expected;
        },
        function ($violation, ?string $detail): string {
            if ($detail === 'not-a-model') {
                return 'Expecting an Eloquent model, but this class is not an Eloquent model. Narrow the selection with ->extending(Illuminate\Database\Eloquent\Model::class).';
            }

            [$actual, $expected] = explode('|', (string) $detail, 2);

            return sprintf(
                "Expecting the table to follow the naming convention, but the model resolves table '%s', expected '%s'. Rename the table, or exclude this class with ->ignoring(...).",
                $actual,
                $expected,
            );
        },
    );
});

expect()->extend('toGuardMassAssignment', function (): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            if (! $object->reflectionClass->isSubclassOf(Model::class)) {
                $detail = 'not-a-model';

                return false;
            }

            if ($object->reflectionClass->isAbstract()) {
                return true;
            }

            /** @var Model $model */
            $model = $object->reflectionClass->newInstanceWithoutConstructor();

            return $model->getFillable() !== [] || $model->getGuarded() !== [];
        },
        fn ($violation, ?string $detail): string => $detail === 'not-a-model'
            ? 'Expecting an Eloquent model, but this class is not one. Narrow the selection with ->extending(Illuminate\Database\Eloquent\Model::class).'
            : 'Expecting the model to restrict mass assignment, but it declares an empty $guarded with no $fillable allow-list, unguarding every attribute. Add a $fillable allow-list, or remove the empty $guarded declaration to rely on the fully guarded default.',
    );
});

expect()->extend('toDeclareNotificationChannels', function () use ($methodOrClassLine): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            $finder = new NodeFinder;

            /** @var array<int, ClassMethod> $methods */
            $methods = $finder->findInstanceOf($object->stmts, ClassMethod::class);

            $via = null;

            foreach ($methods as $method) {
                if ($method->name->toString() === 'via') {
                    $via = $method;

                    break;
                }
            }

            if (! $via instanceof ClassMethod) {
                $detail = 'no-via';

                return false;
            }

            /** @var array<int, Return_> $returns */
            $returns = $finder->findInstanceOf($via->stmts ?? [], Return_::class);

            foreach ($returns as $return) {
                if (! $return->expr instanceof Array_) {
                    // A computed channel list cannot be read statically; treat
                    // it as declared rather than reporting correct code.
                    return true;
                }

                if ($return->expr->items !== []) {
                    return true;
                }
            }

            $detail = 'empty';

            return false;
        },
        fn ($violation, ?string $detail): string => $detail === 'no-via'
            ? 'Expecting the notification to declare its delivery channels, but it declares no via() method.'
            : 'Expecting the notification to declare its delivery channels, but via() returns an empty channel list.',
        $methodOrClassLine('function via'),
    );
});

expect()->extend('toUseMarkdownMailTemplates', function () use ($methodOrClassLine): ArchExpectation {
    return Rule::make(
        $this,
        function (ObjectDescription $object, ?string &$detail): bool {
            $finder = new NodeFinder;

            /** @var array<int, ClassMethod> $methods */
            $methods = $finder->findInstanceOf($object->stmts, ClassMethod::class);

            $content = null;

            foreach ($methods as $method) {
                if ($method->name->toString() === 'content') {
                    $content = $method;

                    break;
                }
            }

            if (! $content instanceof ClassMethod) {
                $detail = 'no-content';

                return false;
            }

            /** @var array<int, Arg> $args */
            $args = $finder->findInstanceOf($content->stmts ?? [], Arg::class);

            foreach ($args as $arg) {
                if ($arg->name?->toString() === 'markdown') {
                    return true;
                }
            }

            $detail = 'no-markdown';

            return false;
        },
        fn ($violation, ?string $detail): string => $detail === 'no-content'
            ? 'Expecting the mailable to render from a markdown template, but it declares no content() method.'
            : 'Expecting the mailable to render from a markdown template, but content() does not name a markdown template. Pass markdown: to Content instead of view:.',
        $methodOrClassLine('function content'),
    );
});
