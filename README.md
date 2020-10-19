# Laravel Deletable

[![Latest Version on Packagist](https://img.shields.io/packagist/v/robotsinside/laravel-deletable.svg?style=flat-square)](https://packagist.org/packages/robotsinside/laravel-deletable)
[![Build Status](https://img.shields.io/travis/robotsinside/laravel-deletable/master.svg?style=flat-square)](https://travis-ci.org/robotsinside/laravel-deletable)
[![Quality Score](https://img.shields.io/scrutinizer/g/robotsinside/laravel-deletable.svg?style=flat-square)](https://scrutinizer-ci.com/g/robotsinside/laravel-deletable)
[![Total Downloads](https://img.shields.io/packagist/dt/robotsinside/laravel-deletable.svg?style=flat-square)](https://packagist.org/packages/robotsinside/laravel-deletable)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

This package can be used to gracefully handle the deletion of Eloquent models which are related to other models through `HasOne`, `HasMany`, `BelongsTo`, `BelongsToMany` or `Morph*` relationships.

It provides a number of helpful additions:

1. Validate delete requests with the provided `DeletableRequest` class
2. Check for the existence of related models before soft deleting a model instance
3. Emulate the cascade behaviour provided at the DB layer

## Installation

Depending on which version of Laravel you're on, you may need to specify which version to install.

| Laravel Version | Package Version |
|:---------------:|:---------------:|
|       8.0       |      ^1.0       |
|       7.0       |      ^0.1       |

1. Run `composer require robotsinside/laravel-deletable`.

2. Optionally register the service provider in `config/app.php`

```php
/*
* Package Service Providers...
*/
\RobotsInside\DeletableServiceProvider::class,
```

Auto-discovery is enabled, so this step can be skipped.

## Usage

Use the `RobotsInside\Deletable\Deletable` trait in your models. You must also define a protected `deletableConfig()` method which returns the configuration array.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use RobotsInside\Deletable\Deletable;

class Post extends Model
{
    use Deletable, SoftDeletes;

    protected function deletableConfig()
    {
        return [
            'relations' => [
                'authors',
            ]
        ]
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }
}
```

## Use cases

### 1. Avoid SQLSTATE[23000]: Integrity constraint violation

A `Post` implements a HasMany relation with a `Like` model.

```php
<?php 

$post = Post::create(['title' => 'My post']);

$like = new Like;
$like->post()->associate($post);
$like->save()

$post->delete(); // SQLSTATE[23000]: Integrity constraint violation
```

To avoid this error and provide the user with some more helpful feedback, we can use the `DeletableRequest` class.

```php
<?php

namespace App\Http\Controllers;

use App\Post;
use RobotsInside\Deletable\Requests\DeletableRequest;

class PostController extends Controller
{
    /**
     * Remove the specified resource from storage.
     *
     * @param DeletableRequest $request
     * @param  Post $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(DeletableRequest $request, Post $post)
    {
        $post->delete();

        return redirect()->route('posts.index');
    }
}
```

Now we can display the Integrity contraint violation as validation errors instead..

```php
<div>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

// Output: This Post has one or more Likes.

```

### 2. Check if a model is deletable

This feature supports all relation types. It's particularly helpful when Laravel's soft deletes are in use, since soft-deleting always succeeds without throwing an Integrity Constraint Violation error.

```php
<?php

$post = Post::create(['title' => 'My post']);

$author = Author::create(['name' => 'Billy Bob']);

$post->authors()->save($author);

if($post->deletable()) {
    $post->delete();
}
```

### 3. Validate deletes

To validate delete requests, you can type-hint the provided `RobotsInside\Deletable\Requests\DeletableRequest` class in your controller method.

This class will attempt to automatically resolve the model's route binding, however it currently only supports a single URI route binding. 

```sh
+-----------+--------------+---------------+----------------------------------------------   
| Method    | URI          | Name          | Action                                          
+-----------+--------------+---------------+----------------------------------------------
| DELETE    | posts/{post} | posts.destroy | App\Http\Controllers\PostController@destroy   
```

If your route has more than one binding, such as `authors/{author}/posts/{post}`, you'll need to create your own form request, which extends `DeletableRequest` and define a `getRouteModel` method which returns the models' route binding.

Below is an example for routes with more than one route binding. This is all that is required for validation to kick in.

```php
<?php

namespace App\Http\Requests;

use RobotsInside\Deletable\Requests\DeletableRequest;

class DeletePostRequest extends DeletableRequest
{
    protected function getRouteModel()
    {
        return 'post';
    }
}
```

As before, type-hint the extended form request in your controller.

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeletePostRequest;

class PostContoller extends Controller
{
    ...

    /**
    * Remove the specified resource from storage.
    *
    * @param  App\Http\Requests\DeletePostRequest;
    * @param  App\Post $post
    * @return \Illuminate\Http\Response
    */
    public function destroy(DeletePostRequest $request, Post $post)
    {
        $post->delete();

        return back();
    }
}
```

## Supported safeDelete modes (use when soft deleting)

When using the safeDelete method, you have the option of defining a mode to be used when deleting a record. The mode can be set on the model's `deletableConfig` array.

- exception (default) (optional)
- cascade
- custom

Note that the `mode` configuration key can be left empty in `exception` mode, but must be set for `cascade` and `custom` modes.

### Exception mode (default)

Soft deleting a model in this situation will fail. If the model in question is referenced by another model, an `UnsafeDeleteException` will be thrown.

```php
<?php

$post = Post::create(['title' => 'My post']);
$author = Author::create(['name' => 'Billy Bob']);
$post->authors()->save($author);

Post::find(1)->safeDelete(); // UnsafeDeleteException
```

### Cascade mode

In this mode related models will also be deleted.

```php
<?php

use App\Post;

$post = Post::create(['title' => 'My post']);
$author = Author::create(['name' => 'Billy Bob']);
$post->authors()->save($author);

Post::find(1)->safeDelete(); // My Post and Billy Bob will be deleted.
```

### Custom mode

1. Set mode to `custom`.
2. Set the handler method
3. Define the handler method on the model

If soft deleting fails, the handler method is called.

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use RobotsInside\Deletable\Deletable;

class Post extends Model
{
    use Deletable, SoftDeletes;

    protected function deletableConfig()
    {
        return [
            'mode' => 'custom',
            'handler' => 'myHandler',
            'relations' => [
                'authors'
            ]
        ];
    }

    public function authors()
    {
        return $this->belongsToMany(Author::class);
    }

    public function myHandler()
    {
        app('log')->info('Unsafe delete of ' . __CLASS__);
    }
}
```

## Testing

Run the provided tests:

```sh
composer test
```

## Security

If you discover any vulnerabilities, please email robertfrancken@gmail.com instead of using the issue tracker.

## Coffee Time

Will work for :coffee::coffee::coffee:

<a href="https://www.buymeacoffee.com/robfrancken" target="_blank" width="50"><img src="https://cdn.buymeacoffee.com/buttons/v2/arial-yellow.png" width="200" alt="Buy Me A Coffee"></a>

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
