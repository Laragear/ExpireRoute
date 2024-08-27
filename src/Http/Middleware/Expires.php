<?php

namespace Laragear\ExpireRoute\Http\Middleware;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\DateFactory;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function array_pad;
use function data_get;
use function explode;
use function get_class;
use function is_numeric;
use function is_string;

class Expires
{
    /**
     * The name of the middleware.
     */
    public const SIGNATURE = 'expires';

    /**
     * The default attribute/property for expiration.
     */
    protected const EXPIRATION_ATTRIBUTE = 'expired_at';

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
    public function handle(Request $request, Closure $next, string $parameter = null, string $relative = null): mixed
    {
        // If there is no parameter to find, fail.
        if (!$parameter ??= $this->getLastRouteParameter($request)) {
            throw new RuntimeException("The path [{$request->path()}] has no route parameter to find an expiration.");
        }

        // Parse the parameter and detach the attribute/property.
        [$parameter, $attribute] = $this->separateParameterFromAttribute($parameter);

        // Let's now find the object of the route parameter.
        $object = $request->route($parameter);

        // If the attribute null, we will try to find the proper attribute name if we're relative or not.
        $attribute = $this->normalizeAttribute($object, $attribute, $relative);

        // If the expiration time is past, then bail out.
        if ($this->date->now() > $this->findTimestamp($object, $attribute, $relative)) {
            $object instanceof Model
                ? throw (new ModelNotFoundException())->setModel(get_class($object), $object->getKey())
                : throw new NotFoundHttpException();
        }

        return $next($request);
    }

    /**
     * Return the last route parameter of the route.
     */
    protected function getLastRouteParameter(Request $request): ?string
    {
        // @phpstan-ignore-next-line
        return Arr::last($request->route()->parameterNames());
    }

    /**
     * Returns the parameter name and the attribute name from the middleware argument string.
     */
    protected function separateParameterFromAttribute(string $parameter): array
    {
        return array_pad(explode('.', $parameter, 2), 2, null);
    }

    /**
     * Finds the proper attribute to check if it wasn't set.
     */
    protected function normalizeAttribute(mixed $object, ?string $attribute, ?string $relative): string
    {
        if ($attribute) {
            return $attribute;
        }

        if (null === $relative) {
            return static::EXPIRATION_ATTRIBUTE;
        }

        return $object->getCreatedAtColumn();
    }

    /**
     * Find the timestamp from the object.
     */
    protected function findTimestamp(mixed $object, string $attribute, ?string $relative): Carbon
    {
        $date = $this->date->parse(data_get($object, $attribute, 'now'));

        if (is_numeric($relative)) {
            $date = $date->add('minutes', (int) $relative);
        } elseif (is_string($relative)) {
            $date = $date->add($relative);
        }

        return $date;
    }

    /**
     * Create a new middleware declaration.
     */
    public static function by(string $parameter): ExpiresDeclaration
    {
        [$parameter, $attribute] = array_pad(explode('.', $parameter, 2), 2, '');

        return new ExpiresDeclaration($parameter, $attribute);
    }
}
