<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class NotRegexRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $this->countRequiredParams(1, $parameters, 'not_regex');

        return preg_match($parameters[0], $value) < 1;
    }
}
