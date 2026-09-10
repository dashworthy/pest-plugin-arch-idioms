<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Eloquent;

use Illuminate\Database\Eloquent\Model;

final class GuardedModel extends Model
{
    protected $guarded = ['id'];
}
