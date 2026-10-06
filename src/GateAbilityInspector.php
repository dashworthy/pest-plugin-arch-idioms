<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms;

/**
 * Compares the permissions that exist with the abilities the code checks, in both directions: a permission nothing
 * checks is dead weight, and an ability no permission backs can never be granted.
 */
final readonly class GateAbilityInspector
{
    /**
     * @param  array<int, string>  $directories  Where to look for Gate::allows() calls.
     * @param  array<int, string>  $alsoChecked  Abilities checked in ways source cannot show, such as names built at
     *                                           runtime or checked by a package.
     */
    public function __construct(
        private array $directories,
        private array $alsoChecked = [],
    ) {}

    /**
     * One message per mismatch; an empty list when the two sides agree.
     *
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public function __invoke(array $permissions): array
    {
        $inSource = GateAbilities::inDirectories($this->directories);
        $checked = array_unique([...$inSource, ...$this->alsoChecked]);
        $failures = [];

        foreach (array_diff($permissions, $checked) as $unused) {
            $failures[] = sprintf("Permission '%s' exists, but nothing checks it with Gate::allows(). Check it where it is needed, or remove the permission.", $unused);
        }

        foreach (array_diff($inSource, $permissions) as $unknown) {
            $failures[] = sprintf("Gate::allows() checks the ability '%s', but no such permission exists, so it can never be granted. Add the permission, or fix the ability name.", $unknown);
        }

        sort($failures);

        return $failures;
    }
}
