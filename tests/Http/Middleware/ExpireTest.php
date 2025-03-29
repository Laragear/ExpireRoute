<?php

namespace Tests\Http\Middleware;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Orchestra\Testbench\Attributes\DefineRoute;
use Tests\Stubs\UserExpirable;
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

    protected function router(string $uri = '/test/{user}', string $middleware = ''): Route
    {
        return $this->app->make('router')->get($uri, fn() => 'ok')->middleware(['web', $middleware]);
    }

    public static function definesRoute($router): void
    {
        $router->get('/user/{user}', fn(UserWithExpirations $user) => $user)->middleware(['web', 'expires']);
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

        UserWithExpirations::$getCreatedAtColumn = Model::CREATED_AT;
        UserWithExpirations::$expiredAt = null;
        UserExpirable::$expiredAt = null;

        parent::setUp();
    }

    public static function definesRouteWithoutField($router): void
    {
        $router->get('/user/test', fn() => 'ok')->middleware(['web', 'expires']);
    }

    #[DefineRoute('definesRouteWithoutField')]
    public function test_throws_when_no_fields(): void
    {
        $request = $this->get('/user/test');

        $request->assertServerError();

        static::assertSame(
            'The path [user/test] has no route parameter to find an expiration.',
            $request->exception->getMessage()
        );
    }

    #[DefineRoute('definesRoute')]
    public function test_uses_last_route_parameter_with_expires_at_attribute(): void
    {
        UserWithExpirations::$expiredAt = now()->addHour();

        $this->get('/user/1')->assertOk();
    }

    #[DefineRoute('definesRoute')]
    public function test_uses_last_route_parameter_with_expires_at_attribute_gone(): void
    {
        UserWithExpirations::$expiredAt = now()->subSecond();

        $this->get('/user/1')->assertStatus(410);

        UserWithExpirations::$expiredAt = now();

        $this->get('/user/1')->assertStatus(410);
    }

    public static function definesRouteWithMultipleParameters($router): void
    {
        $router->get('/user/{user}/number/{number}', fn(UserWithExpirations $user) => $user)
            ->middleware(['web', 'expires:user']);
    }

    #[DefineRoute('definesRouteWithMultipleParameters')]
    public function test_specifies_parameter(): void
    {
        UserWithExpirations::$expiredAt = now()->addHour();

        $this->get('/user/1/number/10')->assertOk();
    }

    #[DefineRoute('definesRouteWithMultipleParameters')]
    public function test_specifies_parameter_gone(): void
    {
        UserWithExpirations::$expiredAt = now()->subSecond();

        $this->get('/user/1/number/10')->assertStatus(410);
    }

    public static function definesRouteWithMultipleParametersAndAttribute($router): void
    {
        $router->get('/user/{user}/number/{number}', fn(UserWithExpirations $user) => $user)
            ->middleware(['web', 'expires:user.customTimestamp']);
    }

    #[DefineRoute('definesRouteWithMultipleParametersAndAttribute')]
    public function test_specifies_parameter_with_attribute(): void
    {
        UserWithExpirations::$expiredAt = now()->addHour();

        $this->get('/user/1/number/10')->assertOk();
    }

    #[DefineRoute('definesRouteWithMultipleParametersAndAttribute')]
    public function test_specifies_parameter_with_attribute_gone(): void
    {
        UserWithExpirations::$expiredAt = now()->subSecond();

        $this->get('/user/1/number/10')->assertStatus(410);
    }

    public static function definesRouteRelative($router): void
    {
        $router->get('/user/{user}', fn(UserWithExpirations $user) => $user)
            ->middleware(['web', 'expires:user,60']);
    }

    #[DefineRoute('definesRouteRelative')]
    public function test_uses_relative_minutes(): void
    {
        $this->get('/user/1')->assertOk();
    }

    #[DefineRoute('definesRouteRelative')]
    public function test_uses_relative_minutes_gone(): void
    {
        User::query()->update(['created_at' => now()->subDay()]);

        $this->get('/user/1')->assertStatus(410);
    }

    public static function definesRouteRelativeString($router): void
    {
        $router->get('/user/{user}', fn(UserWithExpirations $user) => $user)
            ->middleware(['web', 'expires:user,1 hour']);
    }

    #[DefineRoute('definesRouteRelativeString')]
    public function test_uses_relative_time(): void
    {
        $this->get('/user/1')->assertOk();
    }

    #[DefineRoute('definesRouteRelativeString')]
    public function test_uses_relative_time_gone(): void
    {
        User::query()->update(['created_at' => now()->subDay()]);

        $this->get('/user/1')->assertStatus(410);
    }

    public static function definesRouteObject(Router $router): void
    {
        $router->get('/object/{object}', fn($object) => $object)->middleware(['web', 'expires']);
    }

    #[DefineRoute('definesRouteObject')]
    public function test_uses_object_data(): void
    {
        $this->app->make('router')->bind('object', fn() => (object) ['expired_at' => now()->addHour()]);

        $this->get('/object/1')->assertOk();
    }

    #[DefineRoute('definesRouteObject')]
    public function test_uses_object_data_gone(): void
    {
        $this->app->make('router')->bind('object', fn() => (object) ['expired_at' => now()->subSecond()]);

        $this->get('/object/1')->assertStatus(410);
    }

    public static function definesRouteObjectWithParameter(Router $router): void
    {
        $router->get('/object/{object}', fn($object) => $object)->middleware(['web', 'expires:object.foo']);
    }

    #[DefineRoute('definesRouteObjectWithParameter')]
    public function test_uses_object_data_with_parameter(): void
    {
        $this->app->make('router')->bind('object', fn() => (object) ['foo' => now()->addHour()]);

        $this->get('/object/1')->assertOk();
    }

    #[DefineRoute('definesRouteObjectWithParameter')]
    public function test_uses_object_data_with_parameter_gone(): void
    {
        $this->app->make('router')->bind('object', fn() => (object) ['expired_at' => now()->subSecond()]);

        $this->get('/object/1')->assertStatus(410);
    }

    public static function definesRouteObjectWithRelative(Router $router): void
    {
        $router->get('/object/{object}', fn($object) => $object)->middleware(['web', 'expires:object.foo,60']);
    }

    #[DefineRoute('definesRouteObjectWithRelative')]
    public function test_uses_object_data_with_parameter_relative(): void
    {
        $this->app->make('router')->bind('object', fn() => (object) ['foo' => now()->addHour()]);

        $this->get('/object/1')->assertOk();
    }

    #[DefineRoute('definesRouteObjectWithRelative')]
    public function test_uses_object_data_with_parameter_relative_gone(): void
    {
        $this->app->make('router')->bind('object', fn() => (object) ['foo' => now()->subDay()]);

        $this->get('/object/1')->assertStatus(410);
    }

    public static function definesRouteWithExpirable(Router $router): void
    {
        $router->get('/user/{user}', fn(UserExpirable $user) => $user)->middleware(['web', 'expires']);
    }

    #[DefineRoute('definesRouteWithExpirable')]
    public function test_uses_route_expirable_object(): void
    {
        UserExpirable::$expiredAt = now()->addHour();

        $this->get('/user/1')->assertOk();
    }

    #[DefineRoute('definesRouteWithExpirable')]
    public function test_uses_route_expirable_object_gone(): void
    {
        UserExpirable::$expiredAt = now()->subSecond();

        $this->get('/user/1')->assertStatus(410);
    }

    public static function definesRouteWithExpirableAndAttribute(Router $router): void
    {
        $router->get('/user/{user}', fn(UserExpirable $user) => $user)->middleware([
            'web', 'expires:user.customTimestamp'
        ]);
    }

    #[DefineRoute('definesRouteWithExpirableAndAttribute')]
    public function test_uses_route_expirable_attribute_takes_precedence(): void
    {
        UserExpirable::$expiredAt = now()->addHour();

        $this->get('/user/1')->assertOk();
    }

    #[DefineRoute('definesRouteWithExpirableAndAttribute')]
    public function test_uses_route_expirable_attribute_takes_precedence_gone(): void
    {
        UserExpirable::$expiredAt = now()->subSecond();

        $this->get('/user/1')->assertStatus(410);
    }

    public static function definesRouteWithExpirableAndRelative(Router $router): void
    {
        $router->get('/user/{user}', fn(UserExpirable $user) => $user)->middleware([
            'web', 'expires:user,60'
        ]);
    }

    #[DefineRoute('definesRouteWithExpirableAndRelative')]
    public function test_uses_route_expirable_doesnt_uses_relative(): void
    {
        UserExpirable::$expiredAt = now();

        $this->get('/user/1')->assertOk();
    }

    #[DefineRoute('definesRouteWithExpirableAndRelative')]
    public function test_uses_route_expirable_doesnt_uses_relative_gone(): void
    {
        UserExpirable::$expiredAt = now()->subDay();

        $this->get('/user/1')->assertStatus(410);
    }

    #[DefineRoute('definesRouteRelative')]
    public function test_uses_default_created_at_column_if_model_doesnt_uses_timestamps_when_relative(): void
    {
        UserWithExpirations::$getCreatedAtColumn = null;
        UserWithExpirations::$expiredAt = now()->addHour();

        $this->get('/user/1')->assertOk();
    }
}
