<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class BetweenNumberRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, mixed $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(2, $parameters, 'between_number');

        return $value >= $parameters[0] && $value <= $parameters[1];
    }

    /**
     * {@inheritdoc}
     */
    public function replace(string $message, array $parameters): string|null
    {
        return str_replace(['%min%', '%max%'], [$parameters[0], $parameters[1]], $message);
    }
}
