<?php

use RobostsInside\Deletable\Models\Author;
use RobostsInside\Deletable\Models\Post;
use RobostsInside\Deletable\Models\PostCascade;

class UsageTest extends TestCase
{
    protected $post;

    public function setUp() :void
    {
        parent::setUp();

        $this->withFactories(realpath(__DIR__) . '/Factories');
    }

    /** @test */
    public function a_model_with_dependants_is_not_safe_deletable()
    {
        $post = factory(Post::class)->create();
        $author = factory(Author::class)->create();

        $post->authors()->attach($author);

        $this->assertFalse($post->deletable());
    }

    /** @test */
    public function a_model_without_dependants_is_safe_deletable()
    {
        $post = factory(Post::class)->create();

        $this->assertTrue($post->deletable());
    }

    /** @test */
    public function a_model_with_cascade_flag_deletes_related_models_on_safe_soft_delete()
    {
        $post = PostCascade::create(['title' => 'Cascade']);
        $author = factory(Author::class)->create();

        $post->authors()->attach($author);
        $authors = $post->authors;

        $post->safeDelete();

        $post->load('authors');

        $this->assertCount(0, $post->authors);
        $this->assertNotNull($post->deleted_at);

        foreach ($authors as $author) {
            if (in_array('deleted_at', $author->getAttributes())) {
                $this->assertNotNull($author->deleted_at);
            } else {
                $this->assertNull(Author::find($author->id));
            }
        }
    }
}
