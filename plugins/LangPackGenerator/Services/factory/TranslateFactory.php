<?php

namespace Plugin\LangPackGenerator\Services\factory;

use ReflectionClass;
use Symfony\Component\ErrorHandler\Error\ClassNotFoundError;

class TranslateFactory
{
    public function __construct()
    {

    }

    public static function instance($name)
    {
        $name  = ucfirst($name);
        $class = '\\Plugin\\LangPackGenerator\\Services\\' . $name . 'Service';
        if (!class_exists($class)) {

            throw new \Exception("class not found:{$class}");
        }
        $object = new $class();
        return $object;
    }
}
