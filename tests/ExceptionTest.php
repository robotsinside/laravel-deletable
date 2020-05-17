<?php

use RobostsInside\Deletable\Models\Author;
use RobostsInside\Deletable\Models\Post;
use RobostsInside\Deletable\Models\PostInvalidConfig;
use RobotsInside\Deletable\Exceptions\InvalidConfigException;
use RobotsInside\Deletable\Exceptions\UnsafeDeleteException;

class ExceptionTest extends TestCase
{
    protected $post;

    public function setUp() :void
    {
        parent::setUp();

        $this->withFactories(realpath(__DIR__).'/Factories');
    }

    /** @test */
    public function a_model_with_dependants_throws_exeption_when_safe_soft_deleting()
    {
        $post = factory(Post::class)->create();
        $author = factory(Author::class)->create();

        $post->authors()->attach($author);

        $this->expectException(UnsafeDeleteException::class);

        $post->safeSoftDelete();
    }

    /** @test */
    public function a_config_with_invalid_method_throws_exception()
    {
        $post = PostInvalidConfig::create(['title' => 'Bad config']);
        $author = factory(Author::class)->create();

        $post->authors()->attach($author);

        $this->expectException(InvalidConfigException::class);

        $post->safeSoftDelete();
    }
}
