<?php

namespace Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Gate\Nested;

use Illuminate\Support\Facades\Gate;

class ChecksGadgets
{
    public function handle(string $ability): bool
    {
        return Gate::allows('gadgets_index') || Gate::allows($ability);
    }
}
