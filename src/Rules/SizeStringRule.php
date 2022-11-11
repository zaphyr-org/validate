<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class SizeStringRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'size_string');

        return is_string($value) && mb_strlen($value) === (int)$parameters[0];
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): ?string
    {
        return str_replace('%size%', $parameters[0], $message);
    }
}
