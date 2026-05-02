# Expire Route

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/expire-route.svg)](https://packagist.org/packages/laragear/expire-route)
[![Latest stable test run](https://github.com/Laragear/ExpireRoute/actions/workflows/php.yml/badge.svg)](https://github.com/Laragear/ExpireRoute/actions/workflows/php.yml)
[![Codecov Coverage](https://codecov.io/gh/Laragear/ExpireRoute/graph/badge.svg?token=jRXlb5UwCf)](https://codecov.io/gh/Laragear/ExpireRoute)
[![Maintainability](https://qlty.sh/badges/2d622a6d-c1b3-4d87-8fcc-5a7edd9daa7b/maintainability.svg)](https://qlty.sh/gh/Laragear/projects/ExpireRoute)
[![Sonarcloud Status](https://sonarcloud.io/api/project_badges/measure?project=Laragear_ExpireRoute&metric=alert_status)](https://sonarcloud.io/dashboard?id=Laragear_ExpireRoute)
[![Laravel Octane Compatibility](https://img.shields.io/badge/Laravel%20Octane-Compatible-success?style=flat&logo=laravel)](https://laravel.com/docs/11.x/octane#introduction)

Never found models or objects past their expiration time.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;
use App\Models\Party;

Route::get('/payment/{payment}', function (Payment $payment) {
    // ...
})->middleware('expires');
```

## Become a sponsor

[![](.github/assets/support.png)](https://github.com/sponsors/DarkGhostHunter)

Your support allows me to keep this package free, up to date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FExpireRoute&hashtags=PHP,Laravel)**

## Requirements

- PHP 8.3 or later
- Laravel 12 or later

## Installation

Just fire up Composer and require the package in your application

```shell
composer require laragear/expire-route
```

## Usage

While [Laravel Temporarily Protected Routes](https://laravel.com/docs/12.x/urls#signed-urls) works great for making routes available for a given amount of time, this library uses your Eloquent Model in the route to expire it through a middleware.

To better understand how the middleware works, let's imagine we have the `App\Models\Payment` model with an `expires_at` attribute that determines when the payment should be become invalid. The `expires` middleware does this automatically: if the `expires_at` time is past, the request will be aborted with a `HTTP 410 Gone` code.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;

Route::get('payment/{payment}', function (Payment $invite) {
    // ...
})->middleware('expires');
```

### Multiple route parameters

By default, the middleware will always check for the **last route parameter** in a route. You may set the name of the parameter if you require to check its expiration time.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;
use App\Models\Detail;

Route::get('payment/{payment}/detail/{detail}')
    ->uses(function (Payment $payment, Detail $detail) {
        // ...
    })
    ->middleware('expires:payment');
```

### Custom attribute

If your model doesn't have an `expires_at` attribute to check, you can use `dot.notation` to traverse the object attributes and find the expiration time.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;

Route::get('payment/{payment}', function (Payment $payment) {
    // ...
})->middleware('expires:payment.due_at');
```

### Relative expiration

When you have a model that doesn't have an expiration time, you can set a time in minutes (or a string to be parsed by [`strtotime()`](https://www.php.net/manual/function.strtotime.php)) to calculate from the `created_at` attribute when the route should expire.

For example, by setting `60`, the route will expire once 60 minutes have passed since the creation of the `App\Models\Payment` model.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;

Route::get('payment/{payment}', function (Payment $party) {
    // ...
})->middleware('expires:payment,60');
```

If you want to calculate the time from other attribute than `created_at`, issue the name of the attribute using `dot.notation`. 

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;

Route::get('payment/{payment}', function (Payment $party) {
    // ...
})->middleware('expires:payment.issued_at,24 hours');
```

> [!WARNING]
>
> If the property or attribute doesn't exist or returns `null`, it will be assumed the model has **not** expired yet.

### Using the `routeExpiresAt()` method

If the model or object implements the `Laragear\ExpireRoute\Contracts\RouteExpirable` contract, the `routeExpiresAt()` method will be used to retrieve the moment the route it should expire.

```php
namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Laragear\ExpireRoute\Contracts\RouteExpirable;

class Payment extends Model implements RouteExpirable
{
    // ...
    
    public function routeExpiresAt(): DateTimeInterface
    {
        return $this->created_at->addMinutes(60);
    }
}
```

> [!IMPORTANT]
> 
> Using the contract **takes precedence**, unless an attribute is specified by the middleware declaration itself.

## Non Eloquent Models

Both middlewares are not limited to only Eloquent Models. It can be any object (even an array) that has a UNIX Epoch timestamp or a datetime, since the check is done by retrieving the value through [`data_get()`](https://laravel.com/docs/11.x/helpers#method-data-get) and then parsed by Laravel's Date Factory.

```php
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Route;

class Thing
{
    public function __construct(public $expiredAt)
    {
        // 
    }
}

Route::bind('thing', fn($time = 'now') => new Thing($value));

Route::get('some/{thing}', function (Thing $thing) {
    // ...
})->middleware('expires:thing.expiredAt');
```

## Fluent middleware declaration

You may also use the `Laragear\ExpireRoute\Http\Middleware\Expires` middleware to fluently configure it in your route. It's a great way to set relative time expressively.

```php
use Illuminate\Support\Facades\Route;
use Laragear\ExpireRoute\Http\Middleware\Expires;

// Set the parameter name and attribute
Route::get('/payment/{payment}/details/{detail}')
    ->middleware(Expires::using('payment.expiration_time'));

// Set the relative amount of time to check.
Route::get('/payment/{payment}')
    ->middleware(Expires::in(1)->hour()->and(30)->minutes());

// Set the relative amount of time to check.
Route::get('/payment/{payment}')
    ->middleware(Expires::after('60 minutes');
```

## Laravel Octane compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties written during a request.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security-related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at the time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011–2026 Laravel LLC.
