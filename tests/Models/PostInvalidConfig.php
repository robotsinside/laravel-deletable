<?php

namespace RobostsInside\Deletable\Models;

class PostInvalidConfig extends Post
{
    protected $table = 'posts';

    protected function deletableConfig(): array
    {
        return [
            'mode' => 'badmethod',
            'relations' => [
                'authors'
            ]
        ];
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class, 'author_post', 'post_id', 'author_id');
    }
}
