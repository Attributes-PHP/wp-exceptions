<?php

declare(strict_types=1);

namespace Attributes\Wp\Exceptions\Tests\Helpers;

use ReflectionClass;

class Helpers
{
    /**
     * Invokes a private/protected class method and retrieves it's result
     *
     * @param  object  $class  - Class containing the private/protected method.
     * @param  string  $methodName  - The name of the method to be called.
     * @param  array  $args  - Arguments to be sent over method, if needed.
     * @return mixed
     */
    public static function invokeMethod($class, string $methodName, ...$args)
    {
        $className = get_class($class);
        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($class, $args);
    }

    /**
     * Retrieves the value of a private/protected class property
     *
     * @param  object  $class  - Class containing the private/protected property.
     * @param  string  $propertyName  - The name of the property to be retrieved.
     * @return mixed
     */
    public static function getProperty($class, string $propertyName)
    {
        $className = get_class($class);
        $reflection = new ReflectionClass($className);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($class);
    }

    /**
     * Set's the value of a private/protected class property
     *
     * @param  object  $class  - Class containing the private/protected property.
     * @param  string  $propertyName  - The name of the property to be updated.
     * @param  mixed  $propertyValue  - The new value of the property.
     */
    public static function setProperty($class, string $propertyName, $propertyValue): void
    {
        $className = get_class($class);
        $reflection = new ReflectionClass($className);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($class, $propertyValue);
    }

    /**
     * Checks if weather we want to run integration tests or not
     */
    public static function isIntegrationTest(): bool
    {
        return isset($GLOBALS['argv']) && in_array('--group=integration', $GLOBALS['argv'], true);
    }
}
