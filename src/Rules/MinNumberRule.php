<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class MinNumberRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'min_number');

        return is_numeric($value) && (float)($value) >= (float)$parameters[0];
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): ?string
    {
        return str_replace('%min%', $parameters[0], $message);
    }
}
