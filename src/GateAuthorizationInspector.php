<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms;

use Closure;
use PHPUnit\Architecture\Elements\ObjectDescription;

/**
 * Decides whether a request authorizes through a single named permission: its authorize() method checks
 * Gate::allows() with a permission name, and, when the caller supplies a naming convention, the ability is the one
 * the class name implies.
 *
 * Whether that permission exists is GateAbilityInspector's question, asked once for the whole codebase rather than
 * once per request.
 */
final readonly class GateAuthorizationInspector
{
    /**
     * @param  (Closure(string): ?string)|null  $expectedAbility  The ability a request class should check, or null when
     *                                                            any ability will do.
     */
    public function __construct(
        private ?Closure $expectedAbility = null,
    ) {}

    /**
     * The failure message, or null when the request satisfies the rule.
     */
    public function __invoke(ObjectDescription $object): ?string
    {
        $reflection = $object->reflectionClass;

        if ($reflection->isAbstract()) {
            return null;
        }

        if (! $reflection->hasMethod('authorize')) {
            return 'Expecting the request to authorize through a permission, but it declares no authorize() method. '
                .'Add one that returns Gate::allows() with the permission name.';
        }

        $abilities = GateAbilities::inMethod($reflection->getMethod('authorize'));

        if ($abilities === []) {
            return 'Expecting the request to authorize through a permission, but authorize() does not call '
                .'Gate::allows() with a permission name. Add the check.';
        }

        $expected = $this->expectedAbility instanceof Closure ? ($this->expectedAbility)($object->name) : null;

        if ($expected !== null && ! in_array($expected, $abilities, true)) {
            return sprintf(
                "Expecting authorize() to check the '%s' permission this request's name implies, but it checks '%s'. Rename the permission or the request so they agree.",
                $expected,
                implode("', '", $abilities),
            );
        }

        return null;
    }
}
