<?php

namespace Tests\Http\Middleware;

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Route;
use Tests\Stubs\UserWithExpirations;
use Tests\TestCase;
use function now;

class ExpireTest extends TestCase
{
    protected User $user;

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->user = User::forceCreate([
                'name' => 'test',
                'email' => 'test@email.com',
                'password' => 'test_password'
            ]);
        });

        parent::setUp();
    }

    public function test_throws_when_no_fields(): void
    {
        Route::get('/user/test', fn() => 'ok')->middleware('web', 'expires');

        $request = $this->get('/user/test');

        $request->assertServerError();

        static::assertSame(
            'The path [user/test] has no route parameter to find an expiration.',
            $request->exception->getMessage()
        );
    }

    public function test_uses_last_route_parameter_with_expires_at_attribute(): void
    {
        UserWithExpirations::$expiresAt = now()->addHour();

        Route::get('/user/{user}', fn(UserWithExpirations $user) => $user)->middleware('web', 'expires');

        $this->get('/user/1')->assertOk();
    }

    public function test_uses_last_route_parameter_with_expires_at_attribute_not_found(): void
    {
        UserWithExpirations::$expiresAt = now()->subSecond();

        Route::get('/user/{user}', fn(UserWithExpirations $user) => $user)->middleware('web', 'expires');

        $this->get('/user/1')->assertNotFound();
    }

    public function test_specifies_parameter(): void
    {
        UserWithExpirations::$expiresAt = now()->addHour();

        Route::get('/user/{user}/number/{number}', fn(UserWithExpirations $user) => $user)
            ->middleware('web', 'expires:user');

        $this->get('/user/1/number/10')->assertOk();
    }
    public function test_specifies_parameter_not_found(): void
    {
        UserWithExpirations::$expiresAt = now()->subSecond();

        Route::get('/user/{user}/number/{number}', fn(UserWithExpirations $user) => $user)
            ->middleware('web', 'expires:user');

        $this->get('/user/1/number/10')->assertNotFound();
    }

    public function test_specifies_parameter_with_attribute(): void
    {
        UserWithExpirations::$expiresAt = now()->addHour();

        Route::get('/user/{user}/number/{number}', fn(UserWithExpirations $user) => $user)
            ->middleware('web', 'expires:user.customTimestamp');

        $this->get('/user/1/number/10')->assertOk();
    }

    public function test_specifies_parameter_with_attribute_not_found(): void
    {
        UserWithExpirations::$expiresAt = now()->subSecond();

        Route::get('/user/{user}/number/{number}', fn(UserWithExpirations $user) => $user)
            ->middleware('web', 'expires:user.customTimestamp');

        $this->get('/user/1/number/10')->assertNotFound();
    }

    public function test_uses_relative_minutes(): void
    {
        Route::get('/user/{user}', fn(User $user) => $user)->middleware('web', 'expires:user,60');

        $this->get('/user/1')->assertOk();
    }

    public function test_uses_relative_minutes_not_found(): void
    {
        User::query()->update(['created_at' => now()->subDay()]);

        Route::get('/user/{user}', fn(User $user) => $user)->middleware('web', 'expires:user,60');

        $this->get('/user/1')->assertNotFound();
    }

    public function test_uses_relative_time(): void
    {
        Route::get('/user/{user}', fn(User $user) => $user)->middleware('web', 'expires:user,1 hour');

        $this->get('/user/1')->assertOk();
    }

    public function test_uses_relative_time_not_found(): void
    {
        User::query()->update(['created_at' => now()->subDay()]);

        Route::get('/user/{user}', fn(User $user) => $user)->middleware('web', 'expires:user,1 hour');

        $this->get('/user/1')->assertNotFound();
    }

    public function test_uses_object_data(): void
    {
        Route::bind('object', fn() => (object) ['expired_at' => now()->addHour()]);

        Route::get('/object/{object}', fn(User $user) => $user)->middleware('web', 'expires');

        $this->get('/object/1')->assertOk();
    }

    public function test_uses_object_data_not_found(): void
    {
        Route::bind('object', fn() => (object) ['expired_at' => now()->subSecond()]);

        Route::get('/object/{object}', fn(User $user) => $user)->middleware('web', 'expires');

        $this->get('/object/1')->assertNotFound();
    }

    public function test_uses_object_data_with_parameter(): void
    {
        Route::bind('object', fn() => (object) ['foo' => now()->addHour()]);

        Route::get('/object/{object}', fn(User $user) => $user)->middleware('web', 'expires:object.foo');

        $this->get('/object/1')->assertOk();
    }

    public function test_uses_object_data_with_parameter_not_found(): void
    {
        Route::bind('object', fn() => (object) ['foo' => now()->subSecond()]);

        Route::get('/object/{object}', fn(User $user) => $user)->middleware('web', 'expires:object.foo');

        $this->get('/object/1')->assertNotFound();
    }

    public function test_uses_object_data_with_parameter_relative(): void
    {
        Route::bind('object', fn() => (object) ['foo' => now()]);

        Route::get('/object/{object}', fn(User $user) => $user)->middleware('web', 'expires:object.foo,60');

        $this->get('/object/1')->assertOk();
    }

    public function test_uses_object_data_with_parameter_relative_not_found(): void
    {
        Route::bind('object', fn() => (object) ['foo' => now()->subDay()]);

        Route::get('/object/{object}', fn(User $user) => $user)->middleware('web', 'expires:object.foo,60');

        $this->get('/object/1')->assertNotFound();
    }
}
