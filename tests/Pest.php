<?php

declare(strict_types=1);

use PHPUnit\Architecture\Elements\ObjectDescription;
use PHPUnit\Architecture\Services\ServiceContainer;

/**
 * The description arch hands a verb for the class, for testing an inspector without an arch chain.
 *
 * Arch sets up its parser on its first expectation, so a test file that runs before any arch test sets it up here.
 *
 * @param  class-string  $class
 */
function objectDescription(string $class): ObjectDescription
{
    if (! isset(ServiceContainer::$parser)) {
        ServiceContainer::init();
    }

    return ObjectDescription::make((string) (new ReflectionClass($class))->getFileName());
}
