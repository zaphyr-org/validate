<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class BoolRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        return in_array($value, [true, false, 0, 1, '0', '1'], true);
    }
}
