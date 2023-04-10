<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class NumberRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        return is_numeric($value);
    }
}
