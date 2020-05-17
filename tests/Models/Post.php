<?php

namespace RobostsInside\Deletable\Models;

use Illuminate\Database\Eloquent\Model;
use RobotsInside\Deletable\Deletable;

class Post extends Model
{
    use Deletable;

    protected $connection = 'testbench';

    protected function deletableConfig()
    {
        return [
            'mode' => 'exception',
            'relations' => [
                'authors' => [
                    'message' => 'This post is referenced by an author ({name}) and cannot be deleted',
                ],
                'likes'
            ]
        ];
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
