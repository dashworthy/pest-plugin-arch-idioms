<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionMethod;
use SplFileInfo;

/**
 * Finds the abilities source code checks with Gate::allows(), called with a string literal.
 *
 * Text that merely mentions the call, like this docblock, must not quote an ability after it, or it is read as one.
 *
 * Only string literals count. An ability built at runtime cannot be read from source, so whoever needs it must pass it
 * in by other means.
 */
final class GateAbilities
{
    private const string PATTERN = '/Gate::allows\(\s*[\'"]([^\'"]+)[\'"]/';

    /**
     * @return array<int, string>
     */
    public static function inCode(string $code): array
    {
        preg_match_all(self::PATTERN, $code, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * Abilities checked in the body of a method, read from the file that declares it, so an inherited method is read
     * from its parent.
     *
     * @return array<int, string>
     */
    public static function inMethod(ReflectionMethod $method): array
    {
        $file = $method->getFileName();

        if ($file === false) {
            return [];
        }

        $lines = array_slice(
            file($file) ?: [],
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1,
        );

        return self::inCode(implode('', $lines));
    }

    /**
     * Abilities checked anywhere in the PHP files beneath the directories, outside any vendor directory.
     *
     * @param  array<int, string>  $directories
     * @return array<int, string>
     */
    public static function inDirectories(array $directories): array
    {
        $abilities = [];

        foreach ($directories as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));

            /**
             * A package installed on its own has a vendor directory beneath the one being searched, and an ability
             * named in a dependency is not one this code checks.
             *
             * @var SplFileInfo $file
             */
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php' && ! str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR)) {
                    $abilities = [...$abilities, ...self::inCode((string) file_get_contents($file->getPathname()))];
                }
            }
        }

        return array_values(array_unique($abilities));
    }
}
