<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class BetweenArrayRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(2, $parameters, 'between_array');

        return is_array($value) && count($value) >= $parameters[0] && count($value) <= $parameters[1];
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): ?string
    {
        return str_replace(['%min%', '%max%'], [$parameters[0], $parameters[1]], $message);
    }
}
