<?php

namespace Tests\Stubs;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Laragear\ExpireRoute\Contracts\RouteExpirable;

class UserExpirable extends User implements RouteExpirable
{
    protected $table = 'users';

    public static $expiredAt = null;

    protected function expiredAt(): Attribute
    {
        return Attribute::get(fn() => static::$expiredAt);
    }

    protected function customTimestamp(): Attribute
    {
        return Attribute::get(fn() => static::$expiredAt);
    }

    public function routeExpiresAt(): DateTimeInterface
    {
        return static::$expiredAt;
    }
}
