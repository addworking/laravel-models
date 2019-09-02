<?php

namespace Addworking\LaravelModels\Support\Facades;

use Illuminate\Support\Facades\Facade;

class Models extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'laravel-models';
    }
}
