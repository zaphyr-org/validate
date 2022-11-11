<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class AlphaDashRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        return is_string($value) && preg_match('/^[\pL\pM\pN_-]+$/u', $value);
    }
}
