<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

use Zaphyr\Utils\Arr;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class SameRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'same');

        $compareValue = Arr::get($inputs, $parameters[0]);

        return is_string($value) && is_string($compareValue) && $value === $compareValue;
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): ?string
    {
        return str_replace('%other%', $parameters[0], $message);
    }
}
