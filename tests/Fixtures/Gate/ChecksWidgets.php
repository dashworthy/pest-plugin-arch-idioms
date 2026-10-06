<?php

namespace Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Gate;

use Illuminate\Support\Facades\Gate;

class ChecksWidgets
{
    public function handle(): bool
    {
        return Gate::allows('widgets_index') && Gate::allows('widgets_show');
    }
}
