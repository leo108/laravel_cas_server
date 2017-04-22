<?php

/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/29
 * Time: 09:44
 */
class TestCase extends Orchestra\Testbench\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    protected static function getNonPublicMethod($obj, $name)
    {
        $class  = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    protected static function getNonPublicProperty($obj, $name)
    {
        $class    = new ReflectionClass($obj);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property;
    }
}
