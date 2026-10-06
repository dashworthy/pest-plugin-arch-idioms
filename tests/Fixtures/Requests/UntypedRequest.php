<?php

namespace Dashworthy\PestPluginArchIdioms\Tests\Fixtures\Requests;

class UntypedRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string',
            'age' => 'required|max:3',
            'tags' => ['required'],
        ];
    }
}
