<?php

namespace Girover\Tree\Tests;

use Girover\Tree\Models\Nodeable;
use Girover\Tree\Models\Treeable;
use Girover\Tree\TreeServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    public function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Girover\\Tree\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            TreeServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('tree.treeable_model', Treeable::class);
        config()->set('tree.nodeable_model', Nodeable::class);
        // config()->set('database.default', 'mysql');
        $this->configureDatabase();
        // $this->migrateTables();
        /*
        include_once __DIR__.'/../database/migrations/create_tree_table.php.stub';
        (new \CreatePackageTable())->up();
        */
    }

    /**
     * Girover has written this
     * to create tables in memory database
     */
    public function configureDatabase()
    {
       config()->set('database.connections.testing',[
        'driver' => 'mysql',
        'host' => '127.0.0.1',
        'port' => '3306',
        'database' => 'laravel_family_tree_testing',
        'username' => 'root',
        'password' => '',
        'unix_socket' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
       ]); 
    }
}
