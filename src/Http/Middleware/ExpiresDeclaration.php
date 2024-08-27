<?php

namespace Laragear\ExpireRoute\Http\Middleware;

use BadMethodCallException;
use Carbon\CarbonInterval;
use InvalidArgumentException;
use Stringable;
use function array_filter;
use function implode;
use function in_array;

/**
 * @method self second()
 * @method self seconds()
 * @method self minute()
 * @method self minutes()
 * @method self hour()
 * @method self hours()
 * @method self day()
 * @method self days()
 * @method self week()
 * @method self weeks()
 * @method self month()
 * @method self months()
 * @method self year()
 * @method self years()
 */
class ExpiresDeclaration implements Stringable
{
    /**
     * Accepted units to take.
     */
    protected const UNITS = [
        'second',
        'seconds',
        'minute',
        'minutes',
        'hour',
        'hours',
        'day',
        'days',
        'week',
        'weeks',
        'month',
        'months',
        'year',
        'years',
    ];

    /**
     * Create a new middleware declaration.
     */
    public function __construct(
        protected string $parameter,
        protected string $attribute,
        protected CarbonInterval|string $relative = '',
        protected int $amount = 1,
    ) {
        //
    }

    /**
     * The attribute where the timestamp to compare is.
     *
     * @return $this
     */
    public function attribute(string $attribute): static
    {
        $this->attribute = $attribute;

        return $this;
    }

    /**
     * The relative moment from not to check against the model timestamp.
     *
     * @return $this
     */
    public function after(string $interval): static
    {
        $this->relative = $interval;

        return $this;
    }

    /**
     * An amount of time to set.
     *
     * @return $this
     */
    public function in(int $amount): static
    {
        if ($amount < 1) {
            throw new InvalidArgumentException("The amount cannot be below 1, $amount issued.");
        }

        $this->amount = $amount;

        return $this;
    }

    /**
     * An amount of time to set.
     *
     * @return $this
     */
    public function and(int $amount): static
    {
        return $this->in($amount);
    }

    /**
     * Handle dynamic calls to the object.
     */
    public function __call(string $method, array $parameters)
    {
        if (!in_array($method, static::UNITS, true)) {
            throw new BadMethodCallException(sprintf('Call to undefined method %s::%s()', static::class, $method));
        }

        if (! $this->relative instanceof CarbonInterval) {
            $this->relative = new CarbonInterval(0, 0, 0, 0, 0, 0, 0, 0);
        }

        $this->relative = $this->relative->add($method, $this->amount);

        $this->amount = 1;

        return $this;
    }

    /**
     * Transform the object into a string representation.
     */
    public function __toString(): string
    {
        $field = $this->attribute
            ? $this->parameter.'.'.$this->attribute
            : $this->parameter;

        $relative = $this->relative instanceof CarbonInterval
            ? $this->relative->totalMinutes
            : $this->relative;

        return Expires::SIGNATURE.':'.implode(',', array_filter([$field, $relative]));
    }
}
