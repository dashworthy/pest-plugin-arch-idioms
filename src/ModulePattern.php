<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms;

/**
 * The modules of a modular application, named by a namespace pattern in which * matches any one segment, so
 * 'App\Domains\*\*' names every module two levels beneath App\Domains. It finds where a class sits relative to them:
 * which module, and which directory directly beneath that module.
 */
final readonly class ModulePattern
{
    /** @var array<int, string> */
    private array $segments;

    public function __construct(string $pattern)
    {
        $this->segments = explode('\\', trim($pattern, '\\'));
    }

    /**
     * The module a class sits in and the directory directly beneath that module, the directory null when the class
     * sits directly in the module. Null when the class is outside every module.
     *
     * @return array{module: string, directory: string|null}|null
     */
    public function locate(string $class): ?array
    {
        $segments = explode('\\', $class);
        array_pop($segments);

        $depth = count($this->segments);

        if (count($segments) < $depth) {
            return null;
        }

        foreach ($this->segments as $index => $pattern) {
            if ($pattern !== '*' && $pattern !== $segments[$index]) {
                return null;
            }
        }

        return [
            'module' => implode('\\', array_slice($segments, 0, $depth)),
            'directory' => $segments[$depth] ?? null,
        ];
    }
}
