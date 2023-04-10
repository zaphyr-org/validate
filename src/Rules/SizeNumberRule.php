<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class SizeNumberRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'size_number');

        return (float)($value) === (float)$parameters[0];
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): string|null
    {
        return str_replace('%size%', $parameters[0], $message);
    }
}
