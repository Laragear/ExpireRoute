<?php

namespace Tests\Stubs;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Foundation\Auth\User;

class UserWithExpirations extends User
{
    protected $table = 'users';

    public static $expiresAt = null;

    protected function expiredAt(): Attribute
    {
        return Attribute::get(fn() => static::$expiresAt);
    }

    protected function customTimestamp(): Attribute
    {
        return Attribute::get(fn() => static::$expiresAt);
    }
}
