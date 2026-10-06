<?php

declare(strict_types=1);

use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * The description arch hands a verb for the class, for testing an inspector without an arch chain.
 *
 * @param  class-string  $class
 */
function objectDescription(string $class): ObjectDescription
{
    return ObjectDescription::make((string) (new ReflectionClass($class))->getFileName());
}
