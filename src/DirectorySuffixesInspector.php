<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms;

use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Decides whether a class's name ends in the suffix its directory calls for, such as Action for every class in a
 * module's Actions directory, however deeply it is nested there.
 *
 * Only the directories given a suffix are checked. A class in any other directory, directly in a module, or outside
 * the modules is not this rule's concern; toUseApprovedDirectories() decides which directories may exist.
 */
final readonly class DirectorySuffixesInspector
{
    private ModulePattern $modules;

    /**
     * @param  array<string, string>  $suffixes  The suffix each directory's classes end in, keyed by directory name.
     * @param  string  $beneath  The parent namespace, * matching any one segment.
     */
    public function __construct(private array $suffixes, string $beneath)
    {
        $this->modules = new ModulePattern($beneath);
    }

    /**
     * The failure message, or null when the class satisfies the rule.
     */
    public function __invoke(ObjectDescription $object): ?string
    {
        $directory = $this->modules->locate($object->name)['directory'] ?? null;

        if ($directory === null || ! array_key_exists($directory, $this->suffixes)) {
            return null;
        }

        $suffix = $this->suffixes[$directory];

        if (str_ends_with($object->name, $suffix)) {
            return null;
        }

        return sprintf(
            "Expecting every class in the %s directory to end in '%s', but %s does not. Rename it to end in '%s', or move it to the directory for what it is.",
            $directory,
            $suffix,
            substr((string) strrchr('\\'.$object->name, '\\'), 1),
            $suffix,
        );
    }
}
