<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class JsonRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        if (!$value || !is_scalar($value) && !method_exists($value, '__toString')) {
            return false;
        }

        json_decode((string)$value, true);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
