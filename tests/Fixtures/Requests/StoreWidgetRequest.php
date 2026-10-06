<?php

namespace Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests;

use Illuminate\Support\Facades\Gate;

class StoreWidgetRequest extends AbstractWidgetRequest
{
    public function authorize(): bool
    {
        return Gate::allows('widgets_store');
    }

    public function rules(): array
    {
        return ['name' => 'string'];
    }
}
