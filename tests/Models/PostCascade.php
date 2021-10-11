<?php

namespace RobostsInside\Deletable\Models;

class PostCascade extends Post
{
    protected $table = 'posts';

    protected function deletableConfig(): array
    {
        return [
            'mode' => 'cascade',
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
