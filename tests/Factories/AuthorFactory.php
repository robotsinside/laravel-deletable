<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use RobostsInside\Deletable\Models\Author;
use Faker\Generator as Faker;

$factory->define(Author::class, function (Faker $faker) {
    return [
        'name' => $faker->name()
    ];
});
