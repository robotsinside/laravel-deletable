<?php

namespace RobostsInside\Deletable\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $connection = 'testbench';

    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}
