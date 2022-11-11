<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use Countable;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class RequiredRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        if ($value === null) {
            return false;
        }

        if (is_string($value) && trim($value) === '') {
            return false;
        }

        if ((is_array($value) || $value instanceof Countable) && count($value) < 1) {
            return false;
        }

        return true;
    }
}
