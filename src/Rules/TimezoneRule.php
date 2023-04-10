<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use DateTimeZone;
use Throwable;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class TimezoneRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        try {
            new DateTimeZone($value);
        } catch (Throwable) {
            return false;
        }

        return true;
    }
}
