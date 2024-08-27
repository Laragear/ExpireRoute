# Expire Route

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laragear/expire-route.svg)](https://packagist.org/packages/laragear/expire-route)
[![Latest stable test run](https://github.com/Laragear/ExpireRoute/actions/workflows/php.yml/badge.svg?branch=1.x)](https://github.com/Laragear/ExpireRoute/actions/workflows/php.yml)
[![Codecov coverage](https://codecov.io/gh/Laragear/ExpireRoute/branch/1.x/graph/badge.svg?token=jRXlb5UwCf)](https://codecov.io/gh/Laragear/ExpireRoute)
[![CodeClimate Maintainability](https://api.codeclimate.com/v1/badges/6def59b8e483d44bd8b1/maintainability)](https://codeclimate.com/github/Laragear/ExpireRoute/maintainability)
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

Your support allows me to keep this package free, up-to-date and maintainable. Alternatively, you can **[spread the word!](http://twitter.com/share?text=I%20am%20using%20this%20cool%20PHP%20package&url=https://github.com%2FLaragear%2FExpireRoute&hashtags=PHP,Laravel)**

## Usage

The `expires` middleware looks for the `expired_at` attribute or property for last route parameter. Once found, it checks if the current time is below the value.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;

Route::get('payment/{payment}', function (Payment $payment) {
    // ...
})->middleware('expires');
```

If you have multiple route parameters, and you don't want to make the check against the last route parameter, prepend the name of the parameter to the middleware arguments.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;
use App\Models\Detail;

Route::get('payment/{payment}/detail/{detail}', function (Payment $payment, Detail $detail) {
    // ...
})->middleware('expires:payment');
```

By setting the route parameter, you can use `dot.notation` to traverse the object and find the expiration time if it's not the default `expired_at`.

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;
use App\Models\Detail;

Route::get('payment/{payment}/detail/{detail}', function (Payment $payment, Detail $detail) {
    // ...
})->middleware('expires:payment.dates.due_at');
```

If your model doesn't have an expiration time, but you want to calculate the expiration time from another attribute, like the `created_at`, you may issue a second argument as an expiration time. 

If you issue a number, it will be used as the amount of minutes. Any other string will be parsed by [`strtotime()`](https://www.php.net/manual/function.strtotime.php).

```php
use Illuminate\Support\Facades\Route;
use App\Models\Payment;
use App\Models\Detail;
use App\Models\Party;

Route::get('party/{party}', function (Party $party) {
    // ...
})->middleware('expires:party,60');

Route::get('payment/{payment}/detail/{detail}', function (Payment $payment, Detail $detail) {
    // ...
})->middleware('expires:payment.created_at,24 hours');
```

> [!WARNING]
>
> If the property or attribute doesn't exist or returns `null`, it will be assumed the model has not expired yet.

## Non Eloquent Models

Both middlewares are not limited to only Eloquent Models. It can be any object (even an array) that has a timestamp or a datetime, since the check is done by retrieving the value through [`data_get()`](https://laravel.com/docs/11.x/helpers#method-data-get) and then parsed by Laravel's Date Factory.

```php
use Illuminate\Support\Facades\Route;

class Thing
{
    public function __construct(public $expiredAt = 'yesterday')
    {
        // ...
    }
}

Route::bind('thing', fn($value) => new Thing($value));

Route::get('some/{thing}', function (Thing $thing) {
    // ...
})->middleware('expires:thing.expiredAt');
```

## Fluent middleware declaration

You may also use the `Expire` middleware to fluently configure it. It's a great way to set relative time expressively.

```php
use Illuminate\Support\Facades\Route;
use Laragear\ExpireRoute\Http\Middleware\Expires;

Route::get('/payment/{payment}')->middleware(Expires::by('payment')->in(1)->hour()->and(30)->minutes());

Route::get('/payment/{payment}')->middleware(Expires::by('payment')->after('next monday');

Route::get('/payment/{payment}')->middleware(Expires::by('payment.expiration_time'));
```

## Laravel Octane compatibility

- There are no singletons using a stale application instance.
- There are no singletons using a stale config instance.
- There are no singletons using a stale request instance.
- There are no static properties written during a request.

There should be no problems using this package with Laravel Octane.

## Security

If you discover any security related issues, please email darkghosthunter@gmail.com instead of using the issue tracker.

# License

This specific package version is licensed under the terms of the [MIT License](LICENSE.md), at time of publishing.

[Laravel](https://laravel.com) is a Trademark of [Taylor Otwell](https://github.com/TaylorOtwell/). Copyright © 2011-2024 Laravel LLC.
