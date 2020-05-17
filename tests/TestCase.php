<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use RobotsInside\Deletable\DeletableServiceProvider;

class TestCase extends Orchestra\Testbench\TestCase
{
    public function setUp() :void
    {
        parent::setUp();

        Model::unguard();

        $this->artisan('migrate', [
            '--database' => 'testbench',
            '--realpath' => realpath(__DIR__ . '/../migrations'),
        ]);
    }

    protected function getPackageProviders($app)
    {
        return [DeletableServiceProvider::class];
    }

    public function tearDown() :void
    {
        Schema::drop('authors');
        Schema::drop('likes');
        Schema::drop('posts');
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $this->runMigrations();
    }

    private function runMigrations()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('authors', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id')->foreign('post_id')->references('id')->on('posts');
            $table->timestamps();
        });

        Schema::create('author_post', function (Blueprint $table) {
            $table->unsignedInteger('author_id')->foreign()->references('id')->on('authors');
            $table->unsignedInteger('post_id')->foreign()->references('id')->on('posts');
            $table->primary(['author_id', 'post_id']);
        });
    }
}
