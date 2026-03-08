<?php

namespace Laragear\ExpireRoute\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\DateFactory;
use Laragear\ExpireRoute\Contracts\RouteExpirable;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function array_pad;
use function data_get;
use function explode;
use function get_class;
use function is_numeric;

/**
 * @method static \Laragear\ExpireRoute\Http\Middleware\ExpiresDeclaration attribute(string $attribute)
 * @method static \Laragear\ExpireRoute\Http\Middleware\ExpiresDeclaration using(string $parameter)
 * @method static \Laragear\ExpireRoute\Http\Middleware\ExpiresDeclaration after(string $interval)
 * @method static \Laragear\ExpireRoute\Http\Middleware\ExpiresDeclaration in(int $amount)
 * @method static \Laragear\ExpireRoute\Http\Middleware\ExpiresDeclaration and(int $amount)
 */
class Expires
{
    /**
     * The name of the middleware.
     */
    public const SIGNATURE = 'expires';

    /**
     * Create a new middleware instance.
     */
    public function __construct(protected DateFactory $date)
    {
        //
    }

    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next, string $parameter = '', string $relative = ''): mixed
    {
        // If there is no parameter to find, fail.
        if ($parameter === '' && !$parameter = $this->getLastRouteParameter($request)) {
            throw new RuntimeException("The path [{$request->path()}] has no route parameter to find an expiration.");
        }

        // Parse the parameter and detach the attribute/property.
        [$parameter, $attribute] = $this->parseParameter($parameter);

        // Let's now find the object of the route parameter.
        $object = $request->route($parameter);

        $expiresAt = $object instanceof RouteExpirable
            ? $this->parseExpirableTimestamp($object, $attribute, $relative)
            : $this->parseTimestamp($object, $this->normalizeAttribute($object, $attribute, $relative), $relative);

        // If the expiration time is past, then bail out.
        if ($expiresAt->isPast()) {
            $this->throwResponse($object);
        }

        return $next($request);
    }

    /**
     * Return the last route parameter of the route.
     */
    protected function getLastRouteParameter(Request $request): ?string
    {
        return Arr::last($request->route()->parameterNames());
    }

    /**
     * Retrieve the timestamp from the RouteExpirable instance.
     */
    protected function parseExpirableTimestamp(RouteExpirable $expirable, string $attribute, string $relative): Carbon
    {
        $expiresAt = $attribute
            ? $this->parseTimestamp($expirable, $attribute, $relative)
            : $this->date->parse($expirable->routeExpiresAt());

        return $relative ? $this->addRelativeTimeToDatetime($expiresAt, $relative) : $expiresAt;
    }

    /**
     * Returns the parameter name and the attribute name from the middleware argument string.
     *
     * @return array{0: string, 1: string}
     */
    protected function parseParameter(string $parameter): array
    {
        return array_pad(explode('.', $parameter, 2), 2, '');
    }

    /**
     * Finds the proper attribute to check if it wasn't set.
     */
    protected function normalizeAttribute(mixed $object, string $attribute, string $relative): string
    {
        if ($attribute) {
            return $attribute;
        }

        if (!$relative) {
            return 'expired_at';
        }

        // Only return the Created At Column if the model is using one.
        if ($object instanceof Model && $attribute = $object->getCreatedAtColumn()) {
            return $attribute;
        }

        return Model::CREATED_AT;
    }

    /**
     * Find the timestamp from the object.
     */
    protected function parseTimestamp(mixed $object, string $attribute, string $relative): Carbon
    {
        return $this->addRelativeTimeToDatetime(
            $this->date->parse(data_get($object, $attribute) ?? 'yesterday'), $relative
        );
    }

    /**
     * Adds a unit of time to the relative datetime.
     */
    protected function addRelativeTimeToDatetime(Carbon $dateTime, string $relative): Carbon
    {
        return match (true) {
            is_numeric($relative) => $dateTime->add('minutes', (int) $relative),
            $relative !== '' => $dateTime->add($relative),
            default => $dateTime
        };
    }

    /**
     * Throws the response to the browser.
     */
    protected function throwResponse(mixed $object): never
    {
        $message = 'The route has expired.';
        $previous = null;

        if ($object instanceof Model) {
            $previous = (new ModelNotFoundException())->setModel(get_class($object), $object->getKey());
            $message = $previous->getMessage();
        }

        if (method_exists(NotFoundHttpException::class, 'fromStatusCode')) {
            throw NotFoundHttpException::fromStatusCode(410, $message, $previous);
        }

        throw new HttpException(410, $message, $previous);
    }

    /**
     * Dynamically call non-existing methods to the Middleware Declaration.
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return (new ExpiresDeclaration())->{$name}(...$arguments);
    }
}
