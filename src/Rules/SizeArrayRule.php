<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class SizeArrayRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'size_array');

        if (is_array($value) && count($value) === (int)$parameters[0]) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): string|null
    {
        return str_replace('%size%', $parameters[0], $message);
    }
}
