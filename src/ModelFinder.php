<?php

namespace Addworking\LaravelModels;

use Addworking\LaravelClassFinder\ClassFinderFacade as ClassFinder;
use BadMethodCallException;
use Exception;
use Generator;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use RuntimeException;

class ModelFinder
{
    protected $filesystem;

    protected $cache;

    protected $directories;

    protected $cacheKey = "laravel-models";

    protected $aliases = [];

    public function __construct(Filesystem $filesystem, Cache $cache)
    {
        $this->filesystem = $filesystem;
        $this->cache = $cache;
    }

    public function __call($method, $args)
    {
        if (is_null($class = $this->getClassFromBasename($method))) {
            throw new BadMethodCallException("unable to find a suitable model class for '{$method}'".
                ": try adding an alias");
        }

        if (empty($args)) {
            $args = [null];
        }

        return $this->get($class, ...$args);
    }

    public function setAliases(array $aliases): self
    {
        foreach ($aliases as $key => $value) {
            if (! class_exists($value, true)) {
                throw new RuntimeException("class '{$value}' does not exist");
            }
        }

        $this->aliases = $aliases;
        return $this;
    }

    public function setDirectories(array $directories): self
    {
        foreach ($directories as &$directory) {
            $directory = base_path($directory);

            if (! is_dir($directory)) {
                throw new RuntimeException("'{$directory}' is not a valid directory.");
            }
        }

        $this->directories = $directories;
        $this->cache->forget($this->cacheKey);

        return $this;
    }

    public function registerFunctions(): self
    {
        $code = "";
        foreach ($this->classes() as $class) {
            $name  = Str::snake($this->getBasenameFromClass($class));
            $code .= "if(!function_exists('{$name}')){function {$name}(\$id=null){".
                "return \Models::get('{$class}',\$id);}}";
        }

        eval($code);
        return $this;
    }

    public function classes(bool $cached = true): array
    {
        if ($cached && $this->cache->has($this->cacheKey)) {
            return $this->cache->get($this->cacheKey);
        }

        $classes = iterator_to_array($this->getClassesGenerator());

        if ($cached && ! $this->cache->set($this->cacheKey, $classes)) {
            throw new RuntimeException("unable to cache model classes");
        }

        return $classes;
    }

    public function isModel($object): bool
    {
        return $object instanceof Model;
    }

    public function exists($model, $default = null)
    {
        if ($this->isModel($model)) {
            return $model->exists ? $model : $default;
        }

        return $default;
    }

    public function find($id): ?Model
    {
        foreach ($this->classes() as $class) {
            try {
                if ($this->exists($object = $this->get($class, $id))) {
                    return $object;
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    public function findAll(...$id): Collection
    {
        return (new Collection(Arr::flatten($id)))->map(function ($id) {
            return (new Collection($this->classes()))->map(function ($class) use ($id) {
                return $this->exists($object = $this->get($class, $id)) ? $object : null;
            });
        })->flatten()->filter();
    }

    public function get(string $class, $arg)
    {
        if (is_null($arg)) {
            return $class;
        }

        return $this->getModelFromArray($class, $arg)
            ?? $this->getModelFromObject($class, $arg)
            ?? $this->getModelFromId($class, $arg)
            ?? $this->getModelFromName($class, $arg)
            ?? $this->getModelFromNumber($class, $arg)
            ?? $this->getModelFromEmail($class, $arg)
            ?? $this->getEmptyModel($class);
    }

    public function getModelFromObject(string $class, $obj): ?Model
    {
        return $obj instanceof $class ? $obj : null;
    }

    public function getModelFromArray(string $class, $arr): ?Model
    {
        return is_array($arr) ? new $class($arr) : null;
    }

    public function getModelFromId(string $class, $id): ?Model
    {
        if (Uuid::isValid($id)) {
            try {
                return $class::findOrFail(strtolower($id));
            } catch (ModelNotFoundException $e) {
                //
            }
        }

        return null;
    }

    public function getModelFromName(string $class, $name): ?Model
    {
        if (! method_exists($class, 'fromName')) {
            return null;
        }

        if (is_string($class)) {
            try {
                return $class::fromName($name);
            } catch (ModelNotFoundException $e) {
                //
            }
        }

        return null;
    }

    public function getModelFromNumber(string $class, $num): ?Model
    {
        if (! method_exists($class, 'fromNumber')) {
            return null;
        }

        if (is_numeric($num)) {
            try {
                return $class::fromNumber($num);
            } catch (ModelNotFoundException $e) {
                //
            }
        }

        return null;
    }

    public function getModelFromEmail(string $class, $email): ?Model
    {
        if (! method_exists($class, 'fromEmail')) {
            return null;
        }

        if (is_email($email)) {
            try {
                return $class::fromEmail($email);
            } catch (ModelNotFoundException $e) {
                //
            }
        }

        return null;
    }

    public function getEmptyModel(string $class): Model
    {
        return new $class;
    }

    protected function getFilesGenerator(): Generator
    {
        foreach ($this->directories as $directory) {
            foreach ($this->filesystem->allFiles($directory) as $file) {
                if ($file->getExtension() == 'php') {
                    yield $file;
                }
            }
        }
    }

    protected function getClassesGenerator(): Generator
    {
        foreach ($this->getFilesGenerator() as $file) {
            try {
                $class = ClassFinder::pathToClass($file);
            } catch (RuntimeException $e) {
                continue;
            }

            if (class_exists($class, true) && in_array(Model::class, class_parents($class))) {
                yield $class;
            }
        }
    }

    protected function getClassFromBasename(string $name): ?string
    {
        if (isset($this->aliases[Str::snake($name)])) {
            return $this->aliases[Str::snake($name)];
        }

        foreach ($this->classes() as $class) {
            if (class_basename($class) == Str::studly($name)) {
                return $class;
            }
        }

        return null;
    }

    protected function getBasenameFromClass(string $class): string
    {
        if (false !== $key = array_search($class, $this->aliases)) {
            return $key;
        }

        return class_basename($class);
    }
}
