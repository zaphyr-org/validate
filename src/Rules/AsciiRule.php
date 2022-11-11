<?php

declare(strict_types=1);

namespace Zaphyr\Validate\Rules;

/**
 * @author merloxx <merloxx@zaphyr.org>
 */
class AsciiRule extends AbstractRule
{
    /**
     * {@inheritdoc}
     */
    public function validate(string $field, $value, array $parameters, array $inputs): bool
    {
        if (!is_string($value)) {
            return false;
        }

        if (function_exists('mb_detect_encoding')) {
            return mb_detect_encoding($value, 'ASCII', true) === 'ASCII';
        }

        return preg_match('/[^\x00-\x7F]/', $value) === 0;
    }
}
