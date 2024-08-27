<?php

namespace Tests\Http\Middleware;

use BadMethodCallException;
use InvalidArgumentException;
use Laragear\ExpireRoute\Http\Middleware\Expires;
use Orchestra\Testbench\TestCase;

class ExpireDeclarationTest extends TestCase
{
    public function test_sets_parameter(): void
    {
        static::assertEquals('expires:foo', Expires::by('foo'));
    }

    public function test_sets_parameter_with_attribute(): void
    {
        static::assertEquals('expires:foo.bar', Expires::by('foo.bar'));
    }

    public function test_sets_parameter_with_attribute_separately(): void
    {
        static::assertEquals('expires:foo.baz', Expires::by('foo.baz'));
        static::assertEquals('expires:foo.baz', Expires::by('foo.bar')->attribute('baz'));
    }

    public function test_sets_relative_as_string(): void
    {
        static::assertEquals('expires:foo.baz,1 day', Expires::by('foo.baz')->after('1 day'));
    }

    public function test_throws_if_amount_is_below_one(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The amount cannot be below 1, 0 issued.');

        Expires::by('foo.baz')->in(0);
    }

    public function test_sets_relative_as_interval_minutes(): void
    {
        static::assertEquals('expires:foo.baz,1', Expires::by('foo.baz')->in(1)->minute());
        static::assertEquals('expires:foo.baz,1', Expires::by('foo.baz')->in(1)->minutes());
        static::assertEquals('expires:foo.baz,120', Expires::by('foo.baz')->in(2)->hours());
        static::assertEquals('expires:foo.baz,120', Expires::by('foo.baz')->in(2)->hour());
        static::assertEquals('expires:foo.baz,4320', Expires::by('foo.baz')->in(3)->days());
        static::assertEquals('expires:foo.baz,4320', Expires::by('foo.baz')->in(3)->day());
        static::assertEquals('expires:foo.baz,40320', Expires::by('foo.baz')->in(4)->weeks());
        static::assertEquals('expires:foo.baz,40320', Expires::by('foo.baz')->in(4)->week());
        static::assertEquals('expires:foo.baz,201600', Expires::by('foo.baz')->in(5)->months());
        static::assertEquals('expires:foo.baz,201600', Expires::by('foo.baz')->in(5)->month());
        static::assertEquals('expires:foo.baz,2419200', Expires::by('foo.baz')->in(5)->years());
        static::assertEquals('expires:foo.baz,2419200', Expires::by('foo.baz')->in(5)->year());
    }

    public function test_sets_relative_as_interval_minutes_adding_more_items(): void
    {
        static::assertEquals('expires:foo.baz,2', Expires::by('foo.baz')->in(1)->minute()->and(1)->minute());
        static::assertEquals('expires:foo.baz,2', Expires::by('foo.baz')->in(1)->minutes()->and(1)->minute());
        static::assertEquals('expires:foo.baz,121', Expires::by('foo.baz')->in(2)->hours()->and(1)->minute());
        static::assertEquals('expires:foo.baz,121', Expires::by('foo.baz')->in(2)->hour()->and(1)->minute());
        static::assertEquals('expires:foo.baz,4321', Expires::by('foo.baz')->in(3)->days()->and(1)->minute());
        static::assertEquals('expires:foo.baz,4321', Expires::by('foo.baz')->in(3)->day()->and(1)->minute());
        static::assertEquals('expires:foo.baz,40321', Expires::by('foo.baz')->in(4)->weeks()->and(1)->minute());
        static::assertEquals('expires:foo.baz,40321', Expires::by('foo.baz')->in(4)->week()->and(1)->minute());
        static::assertEquals('expires:foo.baz,201601', Expires::by('foo.baz')->in(5)->months()->and(1)->minute());
        static::assertEquals('expires:foo.baz,201601', Expires::by('foo.baz')->in(5)->month()->and(1)->minute());
        static::assertEquals('expires:foo.baz,2419201', Expires::by('foo.baz')->in(5)->years()->and(1)->minute());
        static::assertEquals('expires:foo.baz,2419201', Expires::by('foo.baz')->in(5)->year()->and(1)->minute());
    }

    public function test_throws_bad_method_call_exception(): void
    {
        $this->expectException(BadMethodCallException::class);

        Expires::by('foo.bar')->invalid();
    }
}
