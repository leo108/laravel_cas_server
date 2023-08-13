<?php

namespace Leo108\Cas\Tests;

use Leo108\Cas\CasServerServiceProvider;
use Leo108\Cas\Services\CasConfig;
use Leo108\Cas\Tests\Support\User;

class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $casConfigArr = require __DIR__.'/../config/cas.php';
        $casConfigArr['user_table']['model'] = User::class;
        $this->instance(CasConfig::class, new CasConfig($casConfigArr));
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/Support/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->artisan('migrate');
        $this->beforeApplicationDestroyed(
            fn () => $this->artisan('migrate:rollback')
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            CasServerServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected static function getNonPublicMethod($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    protected static function getNonPublicProperty($obj, $name)
    {
        $class = new \ReflectionClass($obj);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property;
    }
}
