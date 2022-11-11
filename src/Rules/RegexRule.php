<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class RegexRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        $this->countRequiredParams(1, $parameters, 'regex');

        if (!is_string($value)) {
            return false;
        }

        return preg_match($parameters[0], $value) > 0;
    }
}
