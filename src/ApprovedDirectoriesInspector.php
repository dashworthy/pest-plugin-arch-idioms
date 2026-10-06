<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms;

use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Decides whether a class sits in one of the approved directories beneath a parent namespace, such as the
 * Actions, Models and Controllers directories every module of a modular application repeats, and whether it is the
 * kind of class that directory holds.
 *
 * The parent is a namespace pattern in which * matches any one segment, so 'App\Domains\*\*' names every module two
 * levels beneath App\Domains. Only the directory directly beneath the parent is approved or not; a directory may also
 * name the class or interface everything inside it, however deeply nested, must extend or implement. A class outside
 * the parent is not this rule's concern.
 */
final readonly class ApprovedDirectoriesInspector
{
    /**
     * Said with every failure: the list is a decision, so a new directory is asked for, not added.
     */
    public const string APPROVAL = 'If none fits, ask for approval before creating a new directory or adding one to the approved list.';

    /** @var array<string, class-string|null> */
    private array $directories;

    /** @var array<int, string> */
    private array $parent;

    /**
     * @param  array<int|string, string>  $directories  The approved directory names. A name given as a key names the
     *                                                  class or interface every class in that directory must be.
     * @param  string  $beneath  The parent namespace, * matching any one segment.
     */
    public function __construct(array $directories, string $beneath)
    {
        $approved = [];

        foreach ($directories as $key => $value) {
            if (is_string($key)) {
                /** @var class-string $value */
                $approved[$key] = $value;
            } else {
                $approved[$value] = null;
            }
        }

        $this->directories = $approved;
        $this->parent = explode('\\', trim($beneath, '\\'));
    }

    /**
     * The failure message, or null when the class satisfies the rule.
     */
    public function __invoke(ObjectDescription $object): ?string
    {
        $segments = explode('\\', $object->name);
        array_pop($segments);

        $depth = count($this->parent);

        if (count($segments) < $depth) {
            return null;
        }

        foreach ($this->parent as $index => $pattern) {
            if ($pattern !== '*' && $pattern !== $segments[$index]) {
                return null;
            }
        }

        $parent = implode('\\', array_slice($segments, 0, $depth));

        if (count($segments) === $depth) {
            return sprintf(
                'Expecting the class to sit in an approved directory of %s, but it sits directly in %s. Use one of: %s. %s',
                $parent,
                $parent,
                $this->approved(),
                self::APPROVAL,
            );
        }

        $directory = $segments[$depth];

        if (! array_key_exists($directory, $this->directories)) {
            return sprintf(
                "Expecting the class to sit in an approved directory of %s, but '%s' is not one. Use one of: %s. %s",
                $parent,
                $directory,
                $this->approved(),
                self::APPROVAL,
            );
        }

        $type = $this->directories[$directory];

        if ($type !== null && ! is_a($object->name, $type, true)) {
            return sprintf(
                'Expecting every class in the %s directory to be a %s, but this one is not. Extend or implement %s, or move the class to the approved directory for what it is. %s',
                $directory,
                $type,
                $type,
                self::APPROVAL,
            );
        }

        return null;
    }

    private function approved(): string
    {
        $directories = array_keys($this->directories);
        sort($directories);

        return implode(', ', $directories);
    }
}
