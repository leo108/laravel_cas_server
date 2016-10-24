<?php
use Illuminate\Filesystem\ClassFinder;
use Illuminate\Filesystem\Filesystem;

/**
 * Created by PhpStorm.
 * User: leo108
 * Date: 2016/9/29
 * Time: 09:44
 */
class TestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://localhost';

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../vendor/laravel/laravel/bootstrap/app.php';

        $app->register(Leo108\CAS\CASServerServiceProvider::class);

        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Setup DB before each test.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $config = require(__DIR__.'/../config/cas.php');

        $config['user_table']['model'] = User::class;

        $this->app['config']->set('cas', $config);
        $this->app['config']->set('database.default', 'sqlite');
        $this->app['config']->set('database.connections.sqlite.database', ':memory:');

        $this->migrate();
    }

    /**
     * run package database migrations
     *
     * @return void
     */
    public function migrate()
    {
        $fileSystem  = new Filesystem();
        $classFinder = new ClassFinder();

        foreach ($fileSystem->files(__DIR__.'/../database/migrations') as $file) {
            $fileSystem->requireOnce($file);
            $migrationClass = $classFinder->findClass($file);

            (new $migrationClass())->up();
        }

        $usersMigrationFile = __DIR__.'/_support/create_users_table.php';

        $fileSystem->requireOnce($usersMigrationFile);
        $migrationClass = $classFinder->findClass($usersMigrationFile);
        (new $migrationClass())->up();
    }

    protected static function getMethod($obj, $name)
    {
        $class  = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }
}
