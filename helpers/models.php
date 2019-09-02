<?php

use Addworking\LaravelModels\Support\Facades\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

if (! function_exists('exists')) {
    function exists($model, $default = null)
    {
        return Models::exists($model, $default);
    }
}

if (! function_exists('is_model')) {
    function is_model($object): bool
    {
        return Models::isModel($object);
    }
}

if (! function_exists('find')) {
    function find($id): ?Model
    {
        return Models::find($id);
    }
}

if (! function_exists('find_all')) {
    function find_all(...$id): Collection
    {
        return Models::findAll($id);
    }
}

if (! function_exists('get_model_classes')) {
    function get_model_classes()
    {
        return Models::classes();
    }
}

if (! function_exists('get_model_from_object')) {
    function get_model_from_object(string $class, $obj): ?Model
    {
        return Models::getModelFromObject($class, $obj);
    }
}

if (! function_exists('get_model_from_array')) {
    function get_model_from_array(string $class, $arr): ?Model
    {
        return Models::getModelFromArray($class, $arr);
    }
}

if (! function_exists('get_model_from_id')) {
    function get_model_from_id(string $class, $id): ?Model
    {
        return Models::getModelFromId($class, $id);
    }
}

if (! function_exists('get_model_from_name')) {
    function get_model_from_name($class, $str): ?Model
    {
        return Models::getModelFromName($class, $str);
    }
}

if (! function_exists('get_model_from_number')) {
    function get_model_from_number(string $class, $num): ?Model
    {
        return Models::getModelFromNumber($class, $num);
    }
}

if (! function_exists('get_model_from_email')) {
    function get_model_from_email(string $class, $email): ?Model
    {
        return Models::getModelFromEmail($class, $email);
    }
}

if (! function_exists('get_empty_model')) {
    function get_empty_model(string $class): Model
    {
        return Models::getEmptyModel($class);
    }
}

if (! function_exists('get_model')) {
    function get_model(string $class, $arg)
    {
        return Models::get($class, $arg);
    }
}
