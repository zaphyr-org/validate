<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class BailRule extends AbstractRule
{
    /**
     * {@inheritDoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        return true;
    }
}
