# Laravel Deletable

This package can be used to gracefully handle the deletion of Eloquent models which are related to other models through `HasMany`, `BelongsToMany` or `MorphMany` relationships.

It provides a number of helpful additions:

1. Validate delete requests with the provided `DeletableRequest` class
2. Check for the existence of related models before soft deleting a model instance
3. Emulate the cascade behaviour provided at the DB layer

## Installation

1. Run `composer require robotsinside/laravel-deletable`.

2. Optionally register the service provider in `config/app.php`

```php
/*
* Package Service Providers...
*/
\RobotsInside\DeletableServiceProvider::class,
```

Auto-discovery is enabled, so this step can be skipped.

## General usage

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
            'mode' => 'exception',
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

### Scenario 1

A `Post` implements a HasMany relation with a `Like` model. The `likes` table contains a `post_id` foreign key contraint.

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

// This Post has one or more Likes.

```

### Scenario 2

A `Post` implements a `BelongsToMany` relation with an `Author`. The `Post` model leverages Laravel's built in `SoftDeletes` trait. If the `Post` is related to one or more authors, soft deleting the post succeeds, even if a foreign key constraint exists at the database level.

In some situations this might not be what you want and can be avoided by using the `deletable` method.

We have a couple of options to handle this.

#### Option 1

```php
<?php

$post = Post::create(['title' => 'My post']);

$author = Author::create(['name' => 'Billy Bob']);

$post->authors()->save($author);

if($post->deletable()) {
    $post->delete();
}
```

### Validation

To validate delete requests, you can type-hint the provided `RobotsInside\Deletable\Requests\DeletableRequest` class in your controller method.

This class will attempt to automatically resolve the model's route binding, however it currently only supports a single URI route binding. If your route has more than one binding, you must define a `getRouteModel` method which returns the models' route binding.

```sh
+-----------+--------------+---------------+----------------------------------------------   
| Method    | URI          | Name          | Action                                          
+-----------+--------------+---------------+----------------------------------------------
| DELETE    | posts/{post} | posts.destroy | App\Http\Controllers\PostController@destroy   
```

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

class PostContoller extends Controller
{
    ...

    /**
    * Remove the specified resource from storage.
    *
    * @param RobotsInside\Deletable\Requests\DeletableRequest;
    * @param  App\Post $post
    * @return \Illuminate\Http\Response
    */
    public function destroy(DeletableRequest $request, Post $post)
    {
        $post->delete();

        return back();
    }
}
```

### Supported safeDelete modes (use when soft deleting)

When using the safeDelete method, you have the option of defining a mode to be used when deleting a record. The mode can be set on the model's `deletableConfig` array.

- exception (default) (optional)
- cascade
- custom

Note that the `mode` configuration key can be left empty in `exception` mode, but must be set for `cascade` and `custom` modes.

#### Exception mode (default)

Soft deleting a model in this situation will fail. If the model in question is referenced by another model, an `UnsafeDeleteException` will be thrown.

```php
<?php

$post = Post::create(['title' => 'My post']);
$author = Author::create(['name' => 'Billy Bob']);
$post->authors()->save($author);

Post::find(1)->safeDelete(); // UnsafeDeleteException
```

#### Cascade mode

In this mode related models will also be deleted.

```php
<?php

use App\Post;

$post = Post::create(['title' => 'My post']);
$author = Author::create(['name' => 'Billy Bob']);
$post->authors()->save($author);

Post::find(1)->safeDelete(); // My Post and Billy Bob will be deleted.
```

#### Custom mode

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

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
