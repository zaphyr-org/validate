<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class MinArrayRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'min_array');

        if (is_array($value) && count($value) >= $parameters[0]) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): ?string
    {
        return str_replace('%min%', $parameters[0], $message);
    }
}
